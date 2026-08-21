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

        // Include archived products for the vendor, eager load category
        $inventory = Inventory::where('store_id', $store->store_id)
            ->with('category')
            ->orderBy('inventory_id', 'desc')
            ->get()
            ->each->setAppends(['image_url', 'available_quantity']);


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
            'description'     => 'nullable|string',
            'category_id'     => 'required|integer|exists:categories,category_id',
            'product_picture' => 'nullable|image|max:10240',
            // Base price/qty for single item
            'price'           => 'nullable|numeric|min:0',
            'stock_quantity'  => 'nullable|integer|min:0',
            // Variants array
            'variants'        => 'nullable|string', // Will be JSON decoded
        ]);

        // Process variants if any
        $variants = [];
        if (!empty($validated['variants'])) {
            $variants = json_decode($validated['variants'], true);
        }

        $price = $validated['price'] ?? 0;
        $stock = $validated['stock_quantity'] ?? 0;

        if (!empty($variants) && is_array($variants)) {
            $stock = 0;
            $lowestPrice = null;
            foreach ($variants as $variant) {
                $stock += (int)($variant['quantity'] ?? 0);
                $vPrice = (float)($variant['price'] ?? 0);
                if ($lowestPrice === null || $vPrice < $lowestPrice) {
                    $lowestPrice = $vPrice;
                }
            }
            $price = $lowestPrice ?? 0;
        }

        $photoPath = null;
        if ($request->hasFile('product_picture')) {
            $photoPath = $request->file('product_picture')
                ->store('products', 'public');
        }

        $item = Inventory::create([
            'store_id'        => $store->store_id,
            'category_id'     => $validated['category_id'],
            'product_name'    => $validated['product_name'],
            'description'     => $validated['description'] ?? null,
            'price'           => $price,
            'stock_quantity'  => $stock,
            'variants'        => empty($variants) ? null : $variants,
            'product_picture' => $photoPath,
            'status'          => 'active',
        ]);

        return response()->json([
            'message' => 'Product added successfully.',
            'item'    => $item,
        ], 201);
    }

    /**
     * Update an existing product.
     *
     * PATCH /api/vendor/products/{id}
     */
    public function update(Request $request, $id)
    {
        $store = $request->user()->store;
        $item = Inventory::where('store_id', $store->store_id)->findOrFail($id);

        $validated = $request->validate([
            'product_name'    => 'sometimes|string|max:100',
            'description'     => 'nullable|string',
            'category_id'     => 'sometimes|integer|exists:categories,category_id',
            'product_picture' => 'nullable|image|max:10240',
            'price'           => 'nullable|numeric|min:0',
            'stock_quantity'  => 'nullable|integer|min:0',
            'variants'        => 'nullable|string',
            'status'          => 'sometimes|in:active,archived',
        ]);

        if (array_key_exists('variants', $validated)) {
            $variants = [];
            if (!empty($validated['variants'])) {
                $variants = json_decode($validated['variants'], true);
            }

            if (!empty($variants) && is_array($variants)) {
                $stock = 0;
                $lowestPrice = null;
                foreach ($variants as $variant) {
                    $stock += (int)($variant['quantity'] ?? 0);
                    $vPrice = (float)($variant['price'] ?? 0);
                    if ($lowestPrice === null || $vPrice < $lowestPrice) {
                        $lowestPrice = $vPrice;
                    }
                }
                $item->stock_quantity = $stock;
                $item->price = $lowestPrice ?? 0;
                $item->variants = $variants;
            } else {
                $item->variants = null;
                if (isset($validated['price'])) $item->price = $validated['price'];
                if (isset($validated['stock_quantity'])) $item->stock_quantity = $validated['stock_quantity'];
            }
        } else {
            if (isset($validated['price'])) $item->price = $validated['price'];
            if (isset($validated['stock_quantity'])) $item->stock_quantity = $validated['stock_quantity'];
        }

        if (isset($validated['product_name'])) $item->product_name = $validated['product_name'];
        if (array_key_exists('description', $validated)) $item->description = $validated['description'];
        if (isset($validated['category_id'])) $item->category_id = $validated['category_id'];
        if (isset($validated['status'])) $item->status = $validated['status'];

        if ($request->hasFile('product_picture')) {
            $item->product_picture = $request->file('product_picture')->store('products', 'public');
        }

        $item->save();

        return response()->json([
            'message' => 'Product updated successfully.',
            'item'    => $item,
        ]);
    }

    /**
     * Archive a product instead of hard deleting it.
     *
     * DELETE /api/vendor/products/{id}
     */
    public function destroy(Request $request, $id)
    {
        $store = $request->user()->store;
        $item = Inventory::where('store_id', $store->store_id)->findOrFail($id);
        
        $item->status = 'archived';
        $item->save();

        return response()->json([
            'message' => 'Product archived successfully.',
        ]);
    }

    /**
     * Get categories utilized by the vendor.
     *
     * GET /api/vendor/products/categories
     */
    public function categories(Request $request)
    {
        $store = $request->user()->store;
        
        if (!$store) {
            return response()->json([]);
        }

        $categories = \App\Models\Category::withCount(['products' => function ($query) use ($store) {
            $query->where('store_id', $store->store_id)
                  ->where('status', '!=', 'archived');
        }])->orderBy('category_name')->get();

        return response()->json($categories);
    }
}
