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
    public function products(Request $request, DistanceService $distanceService)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        // Seeded/inserted in store-by-store batches, so without an explicit order the results
        // read as "all of store A, then all of store B" — random order gives a mixed catalog.
        $products = Inventory::with(['store', 'category'])
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
                    'category' => $item->category->category_name ?? null,
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

        // If consumer coordinates exist, sort products by nearest store distance first
        if ($lat !== null && $lng !== null) {
            $products = $products->sortBy('distance_meters', SORT_REGULAR, false)->values();
        }

        return response()->json($products);
    }
}
