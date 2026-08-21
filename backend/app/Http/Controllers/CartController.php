<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Checkout items in the cart for a specific store.
     *
     * POST /api/consumer/checkout
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer|exists:stores,store_id',
            'consumer_latitude' => 'nullable|numeric|between:-90,90',
            'consumer_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $consumerId = $request->user()->user_id;
        $storeId = $request->input('store_id');

        // 1. Get all cart items for this consumer + store
        $cartItems = CartItem::with('inventory')
            ->where('consumer_id', $consumerId)
            ->whereHas('inventory', fn ($q) => $q->where('store_id', $storeId))
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'No items in cart for this store.'], 422);
        }

        try {
            // Begin transaction to ensure atomicity
            DB::beginTransaction();

            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($cartItems as $cartItem) {
                // Lock the inventory row to prevent concurrent modification and overselling
                $inventory = Inventory::where('inventory_id', $cartItem->inventory_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->status !== 'active') {
                    throw new \Exception("Product '{$cartItem->inventory->product_name}' is no longer available.");
                }

                $availableQty = $inventory->stock_quantity - $inventory->reserved_quantity;

                if ($availableQty < $cartItem->quantity) {
                    throw new \Exception(
                        "Insufficient stock for '{$inventory->product_name}'. "
                        . "Available: {$availableQty}, Requested: {$cartItem->quantity}."
                    );
                }

                // Reserve the inventory by increasing reserved_quantity
                $inventory->reserved_quantity += $cartItem->quantity;
                $inventory->save();

                $subtotal = $inventory->price * $cartItem->quantity;
                $totalAmount += $subtotal;

                $orderItemsData[] = [
                    'inventory_id' => $inventory->inventory_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $inventory->price,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'consumer_id' => $consumerId,
                'store_id' => $storeId,
                'total_amount' => $totalAmount,
                'status' => 'placed',
                'consumer_latitude' => $request->input('consumer_latitude'),
                'consumer_longitude' => $request->input('consumer_longitude'),
            ]);

            // Create order items
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // Clear the checked-out cart items (only this store's items)
            $cartItemIds = $cartItems->pluck('cart_id');
            CartItem::whereIn('cart_id', $cartItemIds)->delete();

            // Commit transaction and release locks
            DB::commit();

            // Load relationships for the response
            $order->load('items.inventory', 'store');

            return response()->json([
                'message' => 'Order placed successfully.',
                'order' => $order,
            ], 201);

        } catch (\Exception $e) {
            // Rollback everything if any item fails or exception occurs
            DB::rollBack();
            Log::error('Checkout failed: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
