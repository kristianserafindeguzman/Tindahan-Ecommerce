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
    private function getBaseCatalog(Request $request, DistanceService $distanceService, $isPersonalized = false)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $query = Inventory::with(['store', 'category'])
            ->where('status', 'active')
            ->whereHas('store', function ($query) {
                $query->whereHas('approvalStatus', function ($query) {
                    $query->where('status', 'approved');
                });
            });

        if ($isPersonalized) {
            // Enforce available_quantity > 0 for personalized feed
            $query->whereRaw('stock_quantity - reserved_quantity > 0');
        } else {
            // Keep public catalog randomized
            $query->inRandomOrder();
        }

        return $query->get()
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
        $products = $this->getBaseCatalog($request, $distanceService, false);

        // If consumer coordinates exist, sort products by nearest store distance first
        if ($request->query('lat') !== null && $request->query('lng') !== null) {
            $products = $products->sortBy('distance_meters', SORT_REGULAR, false)->values();
        }

        return response()->json($products);
    }

    public function personalizedFeed(Request $request, DistanceService $distanceService)
    {
        $products = $this->getBaseCatalog($request, $distanceService, true);
        $consumerId = $request->user()->user_id;

        // Check if consumer has any search history
        $hasHistory = \App\Models\SearchLog::where('consumer_id', $consumerId)->exists();

        $mlCategories = [];
        $realtimeCategories = [];
        $fallbackCategories = [];
        $isFallback = false;
        
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if ($hasHistory) {
            // PATH A: ML PRECOMPUTED SCORES
            $mlCategories = \App\Models\ConsumerPersonalization::where('consumer_id', $consumerId)
                ->orderBy('predicted_future_searches', 'desc')
                ->pluck('category_id')
                ->toArray();
                
            // REAL-TIME SEARCH OVERLAY (Last 30 days)
            $realtimeCategories = \App\Models\SearchLog::where('consumer_id', $consumerId)
                ->where('searched_at', '>=', now()->subDays(30))
                ->whereNotNull('category_id')
                ->select('category_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as search_count'))
                ->groupBy('category_id')
                ->orderBy('search_count', 'desc')
                ->pluck('category_id')
                ->toArray();
        } else {
            // PATH B: NEW CONSUMER FALLBACK
            $isFallback = true;

            if ($lat !== null && $lng !== null) {
                // 1. Localized Popular Searches
                $latGrid = round((float)$lat, 2);
                $lngGrid = round((float)$lng, 2);

                $fallbackCategories = \App\Models\LocalizedPopularSearch::where('lat_grid', $latGrid)
                    ->where('lng_grid', $lngGrid)
                    ->whereNotNull('category_id')
                    ->orderBy('search_count', 'desc')
                    ->pluck('category_id')
                    ->unique()
                    ->toArray();
                    
                // 2. Top-Selling Nearby Products logic is implicitly handled by scoring later
                // We fetch the top categories from nearby sales
                $nearbyTopCategories = \App\Models\OrderItem::join('orders', 'order_items.order_id', '=', 'orders.order_id')
                    ->join('inventory', 'order_items.inventory_id', '=', 'inventory.inventory_id')
                    ->join('stores', 'orders.store_id', '=', 'stores.store_id')
                    ->where('orders.status', 'picked_up')
                    // Simple bounding box for "nearby" (~5km rough approx)
                    ->whereBetween('stores.latitude', [$lat - 0.05, $lat + 0.05])
                    ->whereBetween('stores.longitude', [$lng - 0.05, $lng + 0.05])
                    ->groupBy('inventory.category_id')
                    ->orderByRaw('SUM(order_items.quantity) DESC')
                    ->limit(5)
                    ->pluck('inventory.category_id')
                    ->toArray();
                    
                // Merge localized search and nearby top sales
                $fallbackCategories = array_values(array_unique(array_merge($fallbackCategories, $nearbyTopCategories)));
            }
        }

        // Create unified deterministic ranking category list
        // Priority: ML Categories -> Realtime Categories not in ML -> Fallback
        $priorityCategories = [];
        foreach ($mlCategories as $catId) {
            if (!in_array($catId, $priorityCategories)) $priorityCategories[] = $catId;
        }
        foreach ($realtimeCategories as $catId) {
            if (!in_array($catId, $priorityCategories)) $priorityCategories[] = $catId;
        }
        foreach ($fallbackCategories as $catId) {
            if (!in_array($catId, $priorityCategories)) $priorityCategories[] = $catId;
        }

        // Rank products
        // Priority 1: Category Match
        // Priority 2: Distance
        // Priority 3: Inventory ID (deterministic tiebreaker)
        $products = $products->sortBy(function ($product) use ($priorityCategories) {
            $catScore = 1000;
            $catId = $product['category_id'];

            if (in_array($catId, $priorityCategories)) {
                $catScore = array_search($catId, $priorityCategories); // 0 is best
            }

            // Return array for multi-level sorting
            return [
                $catScore, 
                $product['distance_meters'] ?? 999999,
                $product['id']
            ];
        })->values();

        return response()->json([
            'is_fallback' => $isFallback,
            'products' => $products
        ]);
    }
}
