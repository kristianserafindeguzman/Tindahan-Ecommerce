<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders for the authenticated consumer.
     *
     * GET /api/consumer/orders
     */
    public function index(Request $request)
    {
        $orders = Order::with(['store', 'items.inventory'])
            ->where('consumer_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Get details for a specific order belonging to the consumer.
     *
     * GET /api/consumer/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = Order::with(['store', 'items.inventory'])
            ->where('consumer_id', $request->user()->user_id)
            ->where('order_id', $id)
            ->firstOrFail();

        return response()->json($order);
    }

    /**
     * Cancel an order if it is still in the 'placed' status.
     *
     * PATCH /api/consumer/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::with('items')->where('consumer_id', $request->user()->user_id)
            ->where('order_id', $id)
            ->firstOrFail();

        if ($order->status !== 'placed') {
            return response()->json(['message' => 'Order cannot be cancelled at this stage.'], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Re-fetch lockForUpdate to ensure transaction integrity if needed,
            // though locking the inventory directly is the main priority.
            
            foreach ($order->items as $item) {
                $inventory = \App\Models\Inventory::where('inventory_id', $item->inventory_id)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                    $inventory->save();
                }
            }

            $order->status = 'cancelled';
            
            if ($request->has('cancellation_reason')) {
                $order->cancellation_reason = $request->input('cancellation_reason');
            }
            
            $order->save();

            \Illuminate\Support\Facades\DB::commit();

            \App\Models\Notification::create([
                'user_id' => $order->consumer_id,
                'order_id' => $order->order_id,
                'title' => 'Order Cancelled',
                'message' => "You successfully cancelled your order #{$order->order_id}.",
            ]);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Consumer order cancellation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export a receipt for a completed order.
     *
     * GET /api/consumer/orders/{id}/receipt
     */
    public function exportReceipt(Request $request, $id)
    {
        $order = Order::with(['consumer', 'items.inventory', 'store'])
            ->where('consumer_id', $request->user()->user_id)
            ->where('order_id', $id)
            ->firstOrFail();

        if ($order->status !== 'picked_up') {
            return response()->json(['message' => 'Receipt only available for completed orders.'], 403);
        }

        $store = $order->store;
        // Get the vendor/owner of the store to display their contact info
        $owner = $store->owner;
        
        $date = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.consumer-order-receipt', [
            'order' => $order,
            'store' => $store,
            'owner' => $owner,
            'date' => $date
        ]);

        return $pdf->download("receipt-order-{$order->order_id}.pdf");
    }
}
