<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Store;
use App\Services\CartReservationService;
use App\Services\StoreHoursService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    private function findVariant($variants, $variantName)
    {
        if (!is_array($variants) || empty($variantName)) return null;
        return collect($variants)->first(function($v) use ($variantName) {
            return ($v['name'] ?? $v['size'] ?? null) === $variantName;
        });
    }

    /** Units of a variant nobody else is holding; $excludeCartId omits one row for callers re-evaluating it, which is safer than adding its hold back to a result clamped at zero. */
    private function getVariantAvailableQuantity($inventoryId, $variantName, $totalVariantStock, $excludeCartId = null)
    {
        $activeCartReservations = CartItem::where('inventory_id', $inventoryId)
            ->where('variant_name', $variantName)
            ->where('expires_at', '>', now())
            ->when($excludeCartId, fn ($q) => $q->where('cart_id', '!=', $excludeCartId))
            ->sum('reserved_quantity');

        $activeOrderReservations = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->where('order_items.inventory_id', $inventoryId)
            ->where('order_items.variant_name', $variantName)
            ->whereIn('orders.status', ['placed', 'preparing', 'ready_for_pickup'])
            ->sum('order_items.quantity');

        return max(0, $totalVariantStock - $activeCartReservations - $activeOrderReservations);
    }

    /**
     * List the authenticated consumer's cart items.
     *
     * GET /api/consumer/cart
     */
    public function index(Request $request, CartReservationService $reservations)
    {
        // Lapsed rows are cleared before listing, so an expired item disappears the moment its owner
        // looks at the cart instead of waiting for the scheduled sweep — no cron required.
        // Showing the cart matters more than tidying it, so a failure here degrades to the old
        // behaviour (the row stays, flagged expired) rather than breaking the page.
        try {
            $reservations->releaseExpiredForConsumer($request->user()->user_id);
        } catch (\Exception $e) {
            Log::error('Failed to release expired reservations while listing cart: ' . $e->getMessage());
        }

        $items = CartItem::with(['inventory.store'])
            ->where('consumer_id', $request->user()->user_id)
            ->get()
            ->map(function ($item) {
                $inventory = $item->inventory;

                $variantName = $item->variant_name;
                $variantPrice = null;
                $variantQuantity = null;

                if ($variantName && is_array($inventory->variants)) {
                    $variant = $this->findVariant($inventory->variants, $variantName);
                    if ($variant) {
                        $variantPrice = (float) ($variant['price'] ?? 0);
                        $variantQuantity = (int) ($variant['quantity'] ?? 0);
                    }
                }

                $price = $variantName && $variantPrice !== null ? $variantPrice : (float) ($inventory->price ?? 0);
                
                // Real-time available check for variants using the helper, else standard
                if ($variantName && $variantQuantity !== null) {
                    $availableQuantity = $this->getVariantAvailableQuantity($inventory->inventory_id, $variantName, $variantQuantity);
                } else {
                    $availableQuantity = $inventory->available_quantity ?? 0;
                }

                // Treat as expired if expires_at is past
                $isExpired = $item->expires_at && $item->expires_at < now();
                
                // If the item itself has expired, we show availableQuantity as 0 
                // so the frontend blocks it until the cleanup command wipes it,
                // or we just return the real available and let frontend block based on expiry.
                // We'll return true availability but let frontend use `expiresAt`.

                return [
                    'cartId' => $item->cart_id,
                    'inventoryId' => $item->inventory_id,
                    'variantName' => $variantName,
                    'name' => $inventory->product_name ?? 'Unavailable product',
                    'image' => $inventory->image_url ?? null,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'availableQuantity' => $availableQuantity,
                    'inStock' => $inventory && $inventory->status === 'active' && $availableQuantity > 0 && !$isExpired,
                    'store' => $inventory->store->store_name ?? null,
                    'storeId' => $inventory->store_id ?? null,
                    'expiresAt' => $item->expires_at ? $item->expires_at->toIso8601String() : null,
                    'isExpired' => $isExpired,
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
    public function store(Request $request, CartReservationService $reservations)
    {
        $request->validate([
            'inventory_id' => 'required|integer|exists:inventory,inventory_id',
            'quantity' => 'nullable|integer|min:1',
            'variant_name' => 'nullable|string|max:100',
        ]);

        $consumerId = $request->user()->user_id;
        $quantity = $request->input('quantity', 1);
        $variantName = $request->input('variant_name');

        return DB::transaction(function () use ($request, $consumerId, $quantity, $variantName, $reservations) {
            $inventory = Inventory::where('inventory_id', $request->inventory_id)
                ->lockForUpdate()
                ->firstOrFail();

            // A lapsed reservation is not a running total to add to, so clear it before measuring anything.
            $reservations->releaseExpiredFor($inventory);

            $query = CartItem::where('consumer_id', $consumerId)
                ->where('inventory_id', $inventory->inventory_id);

            if ($variantName) {
                $query->where('variant_name', $variantName);
            } else {
                $query->whereNull('variant_name');
            }
            $existing = $query->first();

            // Measured after that release, so the freed units count as available again.
            $availableStock = $inventory->available_quantity;
            if ($variantName && is_array($inventory->variants)) {
                $variant = $this->findVariant($inventory->variants, $variantName);
                if (!$variant) {
                    return response()->json(['message' => 'The selected variant is no longer available.'], 422);
                }
                $totalVariantStock = (int) ($variant['quantity'] ?? 0);
                $availableStock = $this->getVariantAvailableQuantity($inventory->inventory_id, $variantName, $totalVariantStock);
            }

            if ($availableStock < 1) {
                return response()->json(['message' => 'This product is out of stock.'], 422);
            }

            $reservationMinutes = config('tindahan.cart_reservation_minutes', 30);
            $expiresAt = now()->addMinutes($reservationMinutes);

            if ($existing) {
                $qtyToAdd = min($quantity, $availableStock);

                $inventory->reserved_quantity += $qtyToAdd;
                $inventory->save();

                $existing->update([
                    'quantity' => $existing->quantity + $qtyToAdd,
                    'reserved_quantity' => $existing->reserved_quantity + $qtyToAdd,
                    'expires_at' => $expiresAt,
                ]);
            } else {
                $qtyToAdd = min($quantity, $availableStock);
                
                $inventory->reserved_quantity += $qtyToAdd;
                $inventory->save();

                CartItem::create([
                    'consumer_id' => $consumerId,
                    'inventory_id' => $inventory->inventory_id,
                    'variant_name' => $variantName,
                    'quantity' => $qtyToAdd,
                    'reserved_quantity' => $qtyToAdd,
                    'expires_at' => $expiresAt,
                ]);
            }

            return response()->json(['message' => 'Added to cart.']);
        });
    }

    /**
     * Update a cart item's quantity.
     *
     * PATCH /api/consumer/cart/{id}
     */
    public function update(Request $request, $id, CartReservationService $reservations)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $id, $reservations) {
            $consumerId = $request->user()->user_id;

            // Read unlocked only to find the product: locks are always taken inventory-first, to match the cleanup service.
            $inventoryId = CartItem::where('cart_id', $id)
                ->where('consumer_id', $consumerId)
                ->value('inventory_id');

            if (!$inventoryId) {
                return response()->json(['message' => 'Cart item not found.'], 404);
            }

            $inventory = Inventory::where('inventory_id', $inventoryId)
                ->lockForUpdate()
                ->firstOrFail();

            $item = CartItem::where('cart_id', $id)
                ->where('consumer_id', $consumerId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return response()->json(['message' => 'Cart item not found.'], 404);
            }

            // Before the release below, which would delete this row and turn a clear message into a bare 404.
            if ($item->expires_at && $item->expires_at < now()) {
                return response()->json([
                    'message' => 'This reservation expired. Remove the item and add it to your cart again.',
                ], 422);
            }

            // Frees other shoppers' lapsed holds so the availability below is honest; this row is live, so untouched.
            $reservations->releaseExpiredFor($inventory);

            $available = $inventory->available_quantity ?? 0;
            if ($item->variant_name && is_array($inventory->variants)) {
                $variant = $this->findVariant($inventory->variants, $item->variant_name);
                if ($variant) {
                    $totalVariantStock = (int) ($variant['quantity'] ?? 0);
                    // Excluded rather than added back, because the helper clamps at zero.
                    $available = $this->getVariantAvailableQuantity($inventory->inventory_id, $item->variant_name, $totalVariantStock, $item->cart_id);
                }
            } else {
                // Add back the item's current reserved quantity for non-variant products
                $available = $available + $item->reserved_quantity;
            }

            if ($available < 1) {
                return response()->json(['message' => 'This product is out of stock.'], 422);
            }

            // Clamped rather than rejected, so the response has to report what was actually stored.
            $newQuantity = min($request->quantity, $available);
            $delta = $newQuantity - $item->reserved_quantity;

            $inventory->reserved_quantity += $delta;
            $inventory->save();

            $reservationMinutes = config('tindahan.cart_reservation_minutes', 30);

            $item->update([
                'quantity' => $newQuantity,
                'reserved_quantity' => $newQuantity,
                'expires_at' => now()->addMinutes($reservationMinutes),
            ]);

            return response()->json([
                'message' => 'Cart updated.',
                'quantity' => $newQuantity,
                'expiresAt' => $item->expires_at->toIso8601String(),
            ]);
        });
    }

    /**
     * Remove an item from the cart.
     *
     * DELETE /api/consumer/cart/{id}
     */
    public function destroy(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $consumerId = $request->user()->user_id;

            // Inventory-first lock ordering, matching update() and the cleanup service.
            $inventoryId = CartItem::where('cart_id', $id)
                ->where('consumer_id', $consumerId)
                ->value('inventory_id');

            if (!$inventoryId) {
                return response()->json(['message' => 'Cart item not found.'], 404);
            }

            $inventory = Inventory::where('inventory_id', $inventoryId)
                ->lockForUpdate()
                ->first();

            $item = CartItem::where('cart_id', $id)
                ->where('consumer_id', $consumerId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                // Already gone — the cleanup job got there first, and it released the hold too.
                return response()->json(['message' => 'Removed from cart.']);
            }

            if ($inventory) {
                $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->reserved_quantity);
                $inventory->save();
            }

            $item->delete();

            return response()->json(['message' => 'Removed from cart.']);
        });
    }

    /**
     * Checkout items in the cart for a specific store.
     *
     * POST /api/consumer/checkout
     */
    public function checkout(Request $request, StoreHoursService $storeHoursService, CartReservationService $reservations)
    {
        $request->validate([
            'store_id' => 'required|integer|exists:stores,store_id',
            'consumer_latitude' => 'nullable|numeric|between:-90,90',
            'consumer_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $consumerId = $request->user()->user_id;
        $storeId = $request->input('store_id');

        $store = Store::findOrFail($storeId);
        if (!$storeHoursService->isOpen($store)) {
            return response()->json([
                'message' => 'This store is currently closed. Please try again during operating hours.',
                'error_code' => 'STORE_CLOSED',
            ], 422);
        }

        // An order with no consumer location leaves the vendor without pickup context, so this
        // is enforced here as well as in the checkout page: the frontend can be bypassed.
        // Checked before the transaction, so a rejected checkout creates no order and reserves
        // no stock. The columns themselves stay nullable, because historical orders predate this.
        $latitude = $request->input('consumer_latitude');
        $longitude = $request->input('consumer_longitude');

        if ($latitude === null || $longitude === null) {
            return response()->json([
                'message' => 'Set your location before checking out, so the store knows where you are ordering from.',
                'error_code' => 'LOCATION_REQUIRED',
            ], 422);
        }

        // 1. Get all cart items for this consumer + store
        // Ordered so every checkout takes its inventory locks in the same sequence. Without this, two
        // shoppers buying the same two products at once can grab them in opposite order and deadlock.
        $cartItems = CartItem::with('inventory')
            ->where('consumer_id', $consumerId)
            ->whereHas('inventory', fn ($q) => $q->where('store_id', $storeId))
            ->orderBy('inventory_id')
            ->orderBy('cart_id')
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
                $productLabel = $cartItem->inventory->product_name ?? 'An item';

                // Lock the inventory row to prevent concurrent modification and overselling
                $inventory = Inventory::where('inventory_id', $cartItem->inventory_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->status !== 'active') {
                    throw new \Exception("Product '{$productLabel}' is no longer available.");
                }

                // The cart was read before this transaction and the cleanup job runs every minute, so the
                // row may have been released since. Re-read it under the lock before trusting its hold.
                $cartItem = CartItem::where('cart_id', $cartItem->cart_id)
                    ->lockForUpdate()
                    ->first();

                if (!$cartItem || ($cartItem->expires_at && $cartItem->expires_at < now())) {
                    throw new \Exception("Cart item '{$productLabel}' has expired. Please refresh your cart.");
                }

                // Released first, so a checkout is never refused over reservations that have already run out.
                $reservations->releaseExpiredFor($inventory);

                $price = $inventory->price;
                $productName = $inventory->product_name;
                $variant = null; // Reset per item: PHP keeps loop variables alive between iterations.

                if ($cartItem->variant_name && is_array($inventory->variants)) {
                    $variant = $this->findVariant($inventory->variants, $cartItem->variant_name);
                    if (!$variant) {
                        throw new \Exception("Variant '{$cartItem->variant_name}' for '{$productName}' is no longer available.");
                    }
                    $price = $variant['price'] ?? 0;
                    $productName = "{$productName} - {$cartItem->variant_name}";
                }

                // Last line of defence: if the cart's reservation ever drifts above real stock, the order stops here.
                if ($cartItem->variant_name && $variant) {
                    $stockOnHand = $this->getVariantAvailableQuantity(
                        $inventory->inventory_id,
                        $cartItem->variant_name,
                        (int) ($variant['quantity'] ?? 0),
                        $cartItem->cart_id
                    );
                } else {
                    // available_quantity may go negative, so adding this item's own hold back reports the true overdraft.
                    $stockOnHand = $inventory->available_quantity + $cartItem->reserved_quantity;
                }

                if ($cartItem->quantity > $stockOnHand) {
                    throw new \Exception("Only {$stockOnHand} left of '{$productName}'. Please update your cart.");
                }

                // Note: We DO NOT increment $inventory->reserved_quantity here because it was already incremented when the item was added to the cart. 
                // The reservation transfers implicitly from cart to order.

                $subtotal = $price * $cartItem->quantity;
                $totalAmount += $subtotal;

                $groupKey = $cartItem->inventory_id . '_' . ($cartItem->variant_name ?? 'default');

                // Group by inventory_id and variant_name to consolidate identical order items
                if (isset($orderItemsData[$groupKey])) {
                    $orderItemsData[$groupKey]['quantity'] += $cartItem->quantity;
                    $orderItemsData[$groupKey]['subtotal'] += $subtotal;
                } else {
                    $orderItemsData[$groupKey] = [
                        'inventory_id' => $cartItem->inventory_id,
                        'variant_name' => $cartItem->variant_name,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $price,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            $order = Order::create([
                'consumer_id' => $consumerId,
                'store_id' => $storeId,
                'total_amount' => $totalAmount,
                'status' => 'placed',
                'consumer_latitude' => $latitude,
                'consumer_longitude' => $longitude,
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

            // The store owner hears about every new order, and a failed notice never undoes the order itself.
            try {
                $ownerId = optional($order->store)->owner_id;
                if ($ownerId) {
                    \App\Models\Notification::create([
                        'user_id' => $ownerId,
                        'order_id' => $order->order_id,
                        'title' => 'New Order',
                        'message' => "Order #{$order->order_id} was placed for ₱" . number_format($totalAmount, 2) . '.',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('New order notification failed: ' . $e->getMessage());
            }

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
