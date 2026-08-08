<?php

namespace App\Http\Controllers;

use App\Models\Inventory;

class CatalogController extends Controller
{
    /**
     * List active products from approved stores, for consumer browsing.
     *
     * GET /api/products
     */
    public function products()
    {
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
            ->map(function ($item) {
                return [
                    'id' => $item->inventory_id,
                    'name' => $item->product_name,
                    'category' => $item->category->category_name ?? null,
                    'price' => (float) $item->price,
                    'image' => $item->image_url,
                    'store' => $item->store->store_name ?? null,
                    'storeId' => $item->store_id,
                    'inStock' => $item->available_quantity > 0,
                ];
            });

        return response()->json($products);
    }
}
