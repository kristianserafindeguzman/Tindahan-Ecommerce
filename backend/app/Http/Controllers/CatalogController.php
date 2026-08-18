<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\DistanceService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * List active products from approved stores, for consumer browsing.
     *
     * GET /api/products
     */
    private function getBaseCatalog(Request $request, DistanceService $distanceService)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        return Inventory::with(['store', 'category'])
            ->where('status', 'active')
            ->whereHas('store', function ($query) {
                $query->whereHas('approvalStatus', function ($query) {
                    $query->where('status', 'approved');
                });
            })
            ->inRandomOrder()
            ->get()
            ->map(function ($item) use ($lat, $lng, $distanceService) {
                $distance = null;
                if ($lat !== null && $lng !== null && $item->store) {
                    $distance = $distanceService->calculateDistance($lat, $lng, $item->store->latitude, $item->store->longitude);
                }

                return [
                    'id' => $item->inventory_id,
                    'name' => $item->product_name,
                    'description' => $item->description,
                    'category' => $item->category->category_name ?? null,
                    'category_id' => $item->category_id, // Needed for personalization ranking
                    'price' => (float) $item->price,
                    'image' => $item->image_url,
                    'store' => $item->store->store_name ?? null,
                    'storeId' => $item->store_id,
                    'inStock' => $item->available_quantity > 0,
                    'availableQuantity' => $item->available_quantity,
                    'variants' => $item->variants,
                    'distance_meters' => $distance,
                ];
            });
    }

    public function products(Request $request, DistanceService $distanceService)
    {
        $products = $this->getBaseCatalog($request, $distanceService);

        // If consumer coordinates exist, sort products by nearest store distance first
        if ($request->query('lat') !== null && $request->query('lng') !== null) {
            $products = $products->sortBy('distance_meters', SORT_REGULAR, false)->values();
        }

        // Remove category_id from output to keep API identical if needed, though adding it is harmless
        return response()->json($products);
    }

    public function personalizedFeed(Request $request, DistanceService $distanceService)
    {
        $products = $this->getBaseCatalog($request, $distanceService);

        // 1. Get user's recent searches
        $recentCategories = \App\Models\SearchLog::where('consumer_id', $request->user()->user_id)
            ->whereNotNull('category_id')
            ->orderBy('searched_at', 'desc')
            ->limit(10)
            ->pluck('category_id')
            ->unique()
            ->toArray();

        // 2. Read ML predictions (trending categories)
        $mlCategories = [];
        $csvPath = base_path('../ml/random_forest_personalization_results.csv');
        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, "r");
            $header = fgetcsv($handle);
            if ($header) {
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    if (isset($data['category_id']) && isset($data['predicted_demand'])) {
                        $catId = (int) $data['category_id'];
                        $mlCategories[$catId] = ($mlCategories[$catId] ?? 0) + (float) $data['predicted_demand'];
                    }
                }
            }
            fclose($handle);
        }
        arsort($mlCategories);
        $trendingCategories = array_keys($mlCategories);

        // 3. Rank products
        // Priority 1: User's recent searches
        // Priority 2: ML trending categories
        // Priority 3: Distance/Random
        $products = $products->sortBy(function ($product) use ($recentCategories, $trendingCategories) {
            $score = 1000;
            $catId = $product['category_id'];

            if (in_array($catId, $recentCategories)) {
                $score = array_search($catId, $recentCategories); // 0 is best
            } elseif (in_array($catId, $trendingCategories)) {
                $score = 50 + array_search($catId, $trendingCategories);
            }

            // Return array for multi-level sorting (score first, then distance)
            return [$score, $product['distance_meters'] ?? 999999];
        })->values();

        return response()->json($products);
    }
}
