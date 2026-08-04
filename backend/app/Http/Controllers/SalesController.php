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
        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }
        $storeId = $vendor->store->store_id;

        $query = Order::where('store_id', $storeId)->where('status', 'picked_up');
        $cancelQuery = Order::where('store_id', $storeId);

        if ($request->has('start_date') && $request->has('end_date')) {
            $formattedStart = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d');
            $formattedEnd = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d');
            
            if ($formattedStart === $formattedEnd) {
                $query->whereDate('updated_at', $formattedStart);
                $cancelQuery->whereDate('updated_at', $formattedStart);
            } else {
                $start = \Carbon\Carbon::parse($formattedStart)->startOfDay();
                $end = \Carbon\Carbon::parse($formattedEnd)->endOfDay();
                $query->whereBetween('updated_at', [$start, $end]);
                $cancelQuery->whereBetween('updated_at', [$start, $end]);
            }
        }
        
        \Illuminate\Support\Facades\Log::info("Sales Metrics Query: " . $query->toSql(), $query->getBindings());

        $revenue = $query->sum('total_amount');
        $orderCount = $query->count();
        $avgOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;

        $revenueGrowth = null;
        if ($request->has('start_date') && $request->has('end_date')) {
            $formattedStart = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d');
            $formattedEnd = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d');
            
            if ($formattedStart === $formattedEnd) {
                $yesterday = \Carbon\Carbon::parse($formattedStart)->subDay()->toDateString();
                $yesterdayRevenue = Order::where('store_id', $storeId)
                    ->where('status', 'picked_up')
                    ->whereDate('updated_at', $yesterday)
                    ->sum('total_amount');
                    
                if ($yesterdayRevenue == 0 && $revenue == 0) {
                    $revenueGrowth = 0;
                } else if ($yesterdayRevenue == 0 && $revenue > 0) {
                    $revenueGrowth = 100;
                } else {
                    $revenueGrowth = round((($revenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 0);
                }
            }
        }

        $totalOrders = $cancelQuery->count();
        $cancelledOrders = (clone $cancelQuery)->where('status', 'cancelled')->count();
        $cancellationRate = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 2) : 0;

        // Calculate actual best-selling category for the selected date/range
        $bestSellingQuery = \Illuminate\Support\Facades\DB::table('orders')
            ->join('order_items', 'orders.order_id', '=', 'order_items.order_id')
            ->join('inventory', 'order_items.inventory_id', '=', 'inventory.inventory_id')
            ->join('categories', 'inventory.category_id', '=', 'categories.category_id')
            ->where('orders.store_id', $storeId)
            ->where('orders.status', 'picked_up');

        if ($request->has('start_date') && $request->has('end_date')) {
            $formattedStart = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d');
            $formattedEnd = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d');
            
            if ($formattedStart === $formattedEnd) {
                $bestSellingQuery->whereDate('orders.updated_at', $formattedStart);
            } else {
                $start = \Carbon\Carbon::parse($formattedStart)->startOfDay();
                $end = \Carbon\Carbon::parse($formattedEnd)->endOfDay();
                $bestSellingQuery->whereBetween('orders.updated_at', [$start, $end]);
            }
        }

        $bestCategoryRecord = $bestSellingQuery
            ->select('categories.category_name', \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('categories.category_name')
            ->orderByDesc('total_qty')
            ->first();

        $bestSellingCategory = $bestCategoryRecord ? $bestCategoryRecord->category_name : 'No Data';

        return response()->json([
            'revenue' => (float) $revenue,
            'avg_order_value' => (float) $avgOrderValue,
            'cancellation_rate' => (float) $cancellationRate,
            'best_selling_category' => $bestSellingCategory,
            'revenue_growth' => $revenueGrowth
        ]);
    }

    /**
     * Get recent sales transactions.
     *
     * GET /api/vendor/sales/transactions
     */
    public function transactions(Request $request)
    {
        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }
        $storeId = $vendor->store->store_id;

        $query = Order::with('items.inventory')->where('store_id', $storeId)->where('status', 'picked_up');

        if ($request->has('start_date') && $request->has('end_date')) {
            $formattedStart = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d');
            $formattedEnd = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d');
            
            if ($formattedStart === $formattedEnd) {
                $query->whereDate('updated_at', $formattedStart);
            } else {
                $start = \Carbon\Carbon::parse($formattedStart)->startOfDay();
                $end = \Carbon\Carbon::parse($formattedEnd)->endOfDay();
                $query->whereBetween('updated_at', [$start, $end]);
            }
        }

        \Illuminate\Support\Facades\Log::info("Sales Transactions Query: " . $query->toSql(), $query->getBindings());

        $transactions = $query->orderBy('updated_at', 'desc')->limit(10)->get()->map(function ($order) {
            $firstItem = $order->items->first();
            $productName = $firstItem && $firstItem->inventory ? $firstItem->inventory->product_name : 'Multiple Items';
            if ($order->items->count() > 1) {
                $productName .= ' (+' . ($order->items->count() - 1) . ' more)';
            }
            
            return [
                'order_id' => $order->order_id,
                'product' => $productName,
                'quantity' => $order->items->sum('quantity'),
                'total' => $order->total_amount,
                'status' => 'Completed'
            ];
        });

        return response()->json($transactions);
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
            'sale_date' => 'required|date_format:Y-m-d',
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
        $saleDate = \Carbon\Carbon::parse($request->sale_date)->setHour(12);

        $order = new Order([
            'consumer_id' => $vendor->user_id, // Map manual sale to the vendor themselves
            'store_id' => $storeId,
            'total_amount' => $request->total_amount,
            'status' => 'picked_up',
        ]);
        $order->timestamps = false;
        $order->created_at = $saleDate;
        $order->updated_at = $saleDate;
        $order->save();

        // Create the OrderItem
        $orderItem = new OrderItem([
            'order_id' => $order->order_id,
            'inventory_id' => $inventory->inventory_id,
            'quantity' => $request->quantity,
            'subtotal' => $request->total_amount,
        ]);
        $orderItem->timestamps = false;
        $orderItem->created_at = $saleDate;
        $orderItem->updated_at = $saleDate;
        $orderItem->save();

        return response()->json(['message' => 'Manual sale recorded successfully']);
    }
}
