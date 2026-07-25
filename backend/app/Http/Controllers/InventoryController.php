<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * List inventory items for the authenticated vendor's store.
     *
     * GET /api/vendor/inventory
     */
    public function index(Request $request)
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'message' => 'No store found for this vendor.',
            ], 404);
        }

        $inventory = Inventory::where('store_id', $store->store_id)->get();

        return response()->json($inventory);
    }

    /**
     * Add a new product to the vendor's store inventory.
     *
     * POST /api/vendor/inventory
     */
    public function store(Request $request)
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'message' => 'No store found for this vendor.',
            ], 404);
        }

        $validated = $request->validate([
            'product_name'    => 'required|string|max:100',
            'price'           => 'required|numeric|min:0',
            'stock_quantity'  => 'required|integer|min:0',
            'category_id'     => 'required|integer|exists:categories,category_id',
            'product_picture' => 'nullable|image|max:10240',
        ]);

        $photoPath = null;
        if ($request->hasFile('product_picture')) {
            $photoPath = $request->file('product_picture')
                ->store('products', 'public');
        }

        $item = Inventory::create([
            'store_id'        => $store->store_id,
            'category_id'     => $validated['category_id'],
            'product_name'    => $validated['product_name'],
            'price'           => $validated['price'],
            'stock_quantity'  => $validated['stock_quantity'],
            'product_picture' => $photoPath,
            'status'          => 'active',
        ]);

        return response()->json([
            'message' => 'Product added successfully.',
            'item'    => $item,
        ], 201);
    }
}
