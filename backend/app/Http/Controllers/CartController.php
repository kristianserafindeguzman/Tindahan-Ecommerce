<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Inventory;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * List the authenticated consumer's cart items.
     *
     * GET /api/consumer/cart
     */
    public function index(Request $request)
    {
        $items = CartItem::with(['inventory.store'])
            ->where('consumer_id', $request->user()->user_id)
            ->get()
            ->map(function ($item) {
                $inventory = $item->inventory;

                return [
                    'cartId' => $item->cart_id,
                    'inventoryId' => $item->inventory_id,
                    'name' => $inventory->product_name ?? 'Unavailable product',
                    'image' => $inventory->image_url ?? null,
                    'price' => (float) ($inventory->price ?? 0),
                    'quantity' => $item->quantity,
                    'availableQuantity' => $inventory->available_quantity ?? 0,
                    'inStock' => $inventory && $inventory->status === 'active' && $inventory->available_quantity > 0,
                    'store' => $inventory->store->store_name ?? null,
                    'storeId' => $inventory->store_id ?? null,
                ];
            });

        return response()->json($items);
    }

    /**
     * Add an item to the authenticated consumer's cart, or increment its
     * quantity if it's already there.
     *
     * POST /api/consumer/cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|integer|exists:inventory,inventory_id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $consumerId = $request->user()->user_id;
        $quantity = $request->input('quantity', 1);

        $inventory = Inventory::findOrFail($request->inventory_id);

        if ($inventory->available_quantity < 1) {
            return response()->json(['message' => 'This product is out of stock.'], 422);
        }

        $existing = CartItem::where('consumer_id', $consumerId)
            ->where('inventory_id', $inventory->inventory_id)
            ->first();

        if ($existing) {
            $existing->update([
                'quantity' => min($existing->quantity + $quantity, $inventory->available_quantity),
            ]);
        } else {
            CartItem::create([
                'consumer_id' => $consumerId,
                'inventory_id' => $inventory->inventory_id,
                'quantity' => min($quantity, $inventory->available_quantity),
            ]);
        }

        return response()->json(['message' => 'Added to cart.']);
    }

    /**
     * Update a cart item's quantity.
     *
     * PATCH /api/consumer/cart/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::where('cart_id', $id)
            ->where('consumer_id', $request->user()->user_id)
            ->firstOrFail();

        $available = $item->inventory->available_quantity ?? 0;

        $item->update([
            'quantity' => min($request->quantity, max($available, 1)),
        ]);

        return response()->json(['message' => 'Cart updated.']);
    }

    /**
     * Remove an item from the cart.
     *
     * DELETE /api/consumer/cart/{id}
     */
    public function destroy(Request $request, $id)
    {
        CartItem::where('cart_id', $id)
            ->where('consumer_id', $request->user()->user_id)
            ->delete();

        return response()->json(['message' => 'Removed from cart.']);
    }
}
