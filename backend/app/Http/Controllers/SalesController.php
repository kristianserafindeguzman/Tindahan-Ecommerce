<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    /**
     * Get sales metrics for the requested date range.
     *
     * GET /api/vendor/sales/metrics
     */
    public function metrics(Request $request)
    {
        $this->validateReportDates($request);

        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }
        $storeId = $vendor->store->store_id;

        $query = Order::where('store_id', $storeId)->where('status', 'picked_up');
        $cancelQuery = Order::where('store_id', $storeId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Carbon\Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay()->setTimezone('UTC');
            $end = \Carbon\Carbon::parse($request->end_date, 'Asia/Manila')->endOfDay()->setTimezone('UTC');
            $query->whereBetween('updated_at', [$start, $end]);
            $cancelQuery->whereBetween('updated_at', [$start, $end]);
        }
        
        \Illuminate\Support\Facades\Log::info("Sales Metrics Query: " . $query->toSql(), $query->getBindings());

        $revenue = $query->sum('total_amount');
        $orderCount = $query->count();
        $avgOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;

        $revenueGrowth = null;
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startManila = \Carbon\Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay();
            $endManila = \Carbon\Carbon::parse($request->end_date, 'Asia/Manila')->endOfDay();
            
            if ($startManila->isSameDay($endManila)) {
                $yesterdayStart = $startManila->copy()->subDay()->setTimezone('UTC');
                $yesterdayEnd = $startManila->copy()->subDay()->endOfDay()->setTimezone('UTC');
                
                $yesterdayRevenue = Order::where('store_id', $storeId)
                    ->where('status', 'picked_up')
                    ->whereBetween('updated_at', [$yesterdayStart, $yesterdayEnd])
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

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Carbon\Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay()->setTimezone('UTC');
            $end = \Carbon\Carbon::parse($request->end_date, 'Asia/Manila')->endOfDay()->setTimezone('UTC');
            $bestSellingQuery->whereBetween('orders.updated_at', [$start, $end]);
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
     * Get sales transactions for a date range, or daily totals for all time.
     *
     * GET /api/vendor/sales/transactions
     */
    public function transactions(Request $request)
    {
        $this->validateReportDates($request);

        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }
        $storeId = $vendor->store->store_id;

        $query = Order::with('items.inventory')->where('store_id', $storeId)->where('status', 'picked_up');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Carbon\Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay()->setTimezone('UTC');
            $end = \Carbon\Carbon::parse($request->end_date, 'Asia/Manila')->endOfDay()->setTimezone('UTC');
            $query->whereBetween('updated_at', [$start, $end]);

            // The report paginates these rows and uses the full result for its order/item totals.
            $transactions = $query->orderByDesc('updated_at')->orderByDesc('order_id')->get()->map(function ($order) {
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
                    'status' => 'Picked up'
                ];
            });

            return response()->json($transactions);
        } else {
            // "All Time" Grouped Logic
            $orders = Order::with('items')
                ->where('store_id', $storeId)
                ->where('status', 'picked_up')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            $grouped = $orders->groupBy(function($order) {
                return \Carbon\Carbon::parse($order->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d');
            });

            $transactions = [];
            foreach ($grouped as $date => $dailyOrders) {
                $dailyRevenue = $dailyOrders->sum('total_amount');
                $totalItems = $dailyOrders->sum(function($order) {
                    return $order->items->sum('quantity');
                });
                
                $transactions[] = [
                    'sale_date' => \Carbon\Carbon::parse($date)->format('M d, Y'),
                    'total_items' => $totalItems,
                    'daily_revenue' => $dailyRevenue
                ];
            }
            
            return response()->json($transactions);
        }
    }

    private function validateReportDates(Request $request): void
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d|required_with:end_date',
            'end_date' => 'nullable|date_format:Y-m-d|required_with:start_date|after_or_equal:start_date',
        ]);
    }

    /**
     * Record a manual sale offline.
     *
     * POST /api/vendor/sales/manual
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|integer|exists:inventory,inventory_id',
            'quantity' => 'required|integer|min:1|max:2147483647',
            'unit_price' => 'required|numeric|min:0|max:999999.99|decimal:0,2',
            'total_amount' => 'required|numeric|min:0',
            'sale_date' => 'required|date_format:Y-m-d|before_or_equal:' . now('Asia/Manila')->toDateString(),
        ], [
            'sale_date.before_or_equal' => 'Sales cannot be recorded for a future date.',
        ]);

        // Calculate in cents so the order total and item subtotal always agree.
        $unitPriceCents = (int) round((float) $validated['unit_price'] * 100);
        $totalCents = $unitPriceCents * (int) $validated['quantity'];
        // order_items.subtotal and unit_price are DECIMAL(8, 2).
        if ($totalCents > 99999999) {
            throw ValidationException::withMessages([
                'total_amount' => 'The sale total must not exceed ₱999,999.99.',
            ]);
        }
        $unitPrice = $unitPriceCents / 100;
        $totalAmount = $totalCents / 100;

        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $storeId = $vendor->store->store_id;

        // Ensure manual sales save exactly the intended date by forcing UTC 
        // since the app uses default UTC for updated_at storage but we want it
        // to show up under that exact day in Asia/Manila.
        $saleDateManila = \Carbon\Carbon::parse($request->sale_date, 'Asia/Manila')->startOfDay();
        $saleDateUtc = clone $saleDateManila;
        $saleDateUtc->setTimezone('UTC');

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($vendor, $storeId, $request, $saleDateUtc, $unitPrice, $totalAmount) {
                // Fetch inventory with lockForUpdate
                $inventory = Inventory::where('inventory_id', $request->inventory_id)
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw new \Exception('Invalid inventory item.');
                }

                $availableQuantity = $inventory->stock_quantity - $inventory->reserved_quantity;

                // Reduce stock
                if ($availableQuantity >= $request->quantity) {
                    $inventory->stock_quantity -= $request->quantity;
                    $inventory->save();
                } else {
                    throw new \Exception('Insufficient stock for this manual sale.');
                }

                // Create the Order
                $order = new Order();
                $order->consumer_id = $vendor->user_id; // Map manual sale to the vendor themselves
                $order->store_id = $storeId;
                $order->total_amount = $totalAmount;
                $order->status = 'picked_up';
                $order->timestamps = false;
                $order->created_at = $saleDateUtc;
                $order->updated_at = $saleDateUtc;
                $order->save();

                // Create the OrderItem
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->order_id;
                $orderItem->inventory_id = $inventory->inventory_id;
                $orderItem->quantity = $request->quantity;
                $orderItem->subtotal = $totalAmount;
                $orderItem->unit_price = $unitPrice;
                $orderItem->save();
            });

            return response()->json(['message' => 'Manual sale recorded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
