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
        $consumerId = $request->user()->user_id;

        // Check if consumer has any search history
        $hasHistory = \App\Models\SearchLog::where('consumer_id', $consumerId)->exists();

        $preferredCategories = [];
        $isFallback = false;

        if ($hasHistory) {
            // PATH A: Individual Personalization
            $preferredCategories = \App\Models\ConsumerPersonalization::where('consumer_id', $consumerId)
                ->orderBy('predicted_future_searches', 'desc')
                ->pluck('category_id')
                ->toArray();
        } else {
            // PATH B: Localized Popular Searches / New Consumer Fallback
            $isFallback = true;
            $lat = $request->query('lat');
            $lng = $request->query('lng');

            if ($lat !== null && $lng !== null) {
                $latGrid = round((float)$lat, 2);
                $lngGrid = round((float)$lng, 2);

                $preferredCategories = \App\Models\LocalizedPopularSearch::where('lat_grid', $latGrid)
                    ->where('lng_grid', $lngGrid)
                    ->whereNotNull('category_id')
                    ->orderBy('search_count', 'desc')
                    ->pluck('category_id')
                    ->unique()
                    ->toArray();
            }
        }

        // Rank products
        // Priority 1: ML Preferred Categories (Path A or Path B)
        // Priority 2: Distance
        $products = $products->sortBy(function ($product) use ($preferredCategories) {
            $score = 1000;
            $catId = $product['category_id'];

            if (in_array($catId, $preferredCategories)) {
                $score = array_search($catId, $preferredCategories); // 0 is best
            }

            // Return array for multi-level sorting (score first, then distance)
            return [$score, $product['distance_meters'] ?? 999999];
        })->values();

        return response()->json([
            'is_fallback' => $isFallback,
            'products' => $products
        ]);
    }
}
