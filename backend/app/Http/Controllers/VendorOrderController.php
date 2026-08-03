<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VendorOrderController extends Controller
{
    /**
     * Get all orders belonging to the vendor's store.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $store = $user->store;
        if (!$store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        $orders = Order::with('consumer', 'items.inventory', 'store')
            ->where('store_id', $store->store_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Get details for a specific order belonging to the vendor.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'Vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $store = $user->store;
        if (!$store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        $order = Order::with(['consumer', 'items.inventory', 'store'])
            ->where('store_id', $store->store_id)
            ->where('order_id', $id)
            ->firstOrFail();

        return response()->json($order);
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'Vendor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $store = $user->store;
        if (!$store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        $order = Order::with('items')->where('store_id', $store->store_id)
            ->where('order_id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['placed', 'preparing', 'ready_for_pickup', 'picked_up', 'cancelled'])]
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        if ($oldStatus === $newStatus) {
            return response()->json(['message' => 'Status is already ' . $newStatus], 400);
        }

        // Handle inventory logic based on status transition within a transaction
        try {
            DB::beginTransaction();

            if ($newStatus === 'picked_up' && $oldStatus !== 'picked_up') {
                // Deduct from stock AND reserved
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('inventory_id', $item->inventory_id)->lockForUpdate()->first();
                    if ($inventory) {
                        $inventory->stock_quantity -= $item->quantity;
                        // Avoid negative reserved quantity just in case it wasn't reserved properly
                        $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                        $inventory->save();
                    }
                }
            } elseif ($newStatus === 'cancelled' && $oldStatus !== 'cancelled' && $oldStatus !== 'picked_up') {
                // Lift reservation
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('inventory_id', $item->inventory_id)->lockForUpdate()->first();
                    if ($inventory) {
                        $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                        $inventory->save();
                    }
                }
            } elseif ($oldStatus === 'picked_up' || $oldStatus === 'cancelled') {
                // Cannot transition out of terminal states (for simplicity in this flow)
                throw new \Exception('Cannot change status from a terminal state (' . $oldStatus . ').');
            }

            $order->status = $newStatus;
            
            if ($newStatus === 'cancelled' && $request->has('cancellation_reason')) {
                $order->cancellation_reason = $request->input('cancellation_reason');
            }
            
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Order status update failed: ' . $e->getMessage() . ' - Stack trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to update order status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a distinct list of customers who have ordered from this vendor.
     */
    public function customers(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Vendor' || !$user->store) {
            return response()->json(['message' => 'Unauthorized or store not found'], 403);
        }

        $storeId = $user->store->store_id;

        // Get distinct consumer IDs who ordered from this store
        $consumerIds = Order::where('store_id', $storeId)
            ->distinct()
            ->pluck('consumer_id');

        $customers = \App\Models\User::whereIn('user_id', $consumerIds)
            ->select('user_id', 'full_name', 'phone_number', 'profile_picture')
            ->get()
            ->map(function ($customer) {
                return [
                    'user_id' => $customer->user_id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'profile_picture_url' => $customer->profile_picture ? asset('storage/' . $customer->profile_picture) : null
                ];
            });

        return response()->json($customers);
    }

    /**
     * Get all orders for a specific customer from this vendor.
     */
    public function customerOrders(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'Vendor' || !$user->store) {
            return response()->json(['message' => 'Unauthorized or store not found'], 403);
        }

        $storeId = $user->store->store_id;

        $orders = Order::where('store_id', $storeId)
            ->where('consumer_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }
}
