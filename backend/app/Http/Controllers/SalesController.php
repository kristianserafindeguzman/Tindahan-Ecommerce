<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;

class SalesController extends Controller
{
    /**
     * Get basic sales metrics (Skeleton: returns 0).
     *
     * GET /api/vendor/sales/metrics
     */
    public function metrics(Request $request)
    {
        return response()->json([
            'revenue' => 0,
            'avg_order_value' => 0,
            'cancellation_rate' => 0,
            'best_selling_category' => null
        ]);
    }

    /**
     * Get recent sales transactions (Skeleton: returns empty array).
     *
     * GET /api/vendor/sales/transactions
     */
    public function transactions(Request $request)
    {
        return response()->json([]);
    }

    /**
     * Record a manual sale offline.
     *
     * POST /api/vendor/sales/manual
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|integer|exists:inventory,inventory_id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $storeId = $vendor->store->store_id;

        // Verify inventory belongs to vendor
        $inventory = Inventory::where('inventory_id', $request->inventory_id)
            ->where('store_id', $storeId)
            ->first();

        if (!$inventory) {
            return response()->json(['message' => 'Invalid inventory item.'], 400);
        }

        // Reduce stock
        if ($inventory->stock_quantity >= $request->quantity) {
            $inventory->stock_quantity -= $request->quantity;
            $inventory->save();
        } else {
            return response()->json(['message' => 'Insufficient stock for this manual sale.'], 400);
        }

        // Create the Order
        $order = Order::create([
            'consumer_id' => $vendor->user_id, // Map manual sale to the vendor themselves or a generic user. We'll map to the vendor.
            'store_id' => $storeId,
            'total_amount' => $request->total_amount,
            'status' => 'completed',
        ]);

        // Create the OrderItem
        OrderItem::create([
            'order_id' => $order->order_id,
            'inventory_id' => $inventory->inventory_id,
            'quantity' => $request->quantity,
            'subtotal' => $request->total_amount,
        ]);

        return response()->json(['message' => 'Manual sale recorded successfully']);
    }
}
