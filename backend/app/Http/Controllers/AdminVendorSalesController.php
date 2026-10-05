<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVendorSalesController extends Controller
{
    public function show(Request $request, $storeId)
    {
        // Deleted stores retain their sales history for administrative review.
        Store::withTrashed()->findOrFail($storeId);
        [$start, $end] = $this->dateRange($request);

        $orders = Order::where('store_id', $storeId)->whereBetween('updated_at', [
            $start->copy()->utc(), $end->copy()->endOfDay()->utc(),
        ]);
        $sales = (clone $orders)->where('status', 'picked_up');
        $revenue = (float) (clone $sales)->sum('total_amount');
        $completed = (clone $sales)->count();
        $total = (clone $orders)->count();
        $cancelled = (clone $orders)->where('status', 'cancelled')->count();
        $units = DB::table('order_items')->whereIn('order_id', (clone $sales)->select('order_id'))->sum('quantity');

        $grouped = (clone $sales)->get(['order_id', 'total_amount', 'updated_at'])->groupBy(
            fn ($order) => $order->updated_at->copy()->setTimezone('Asia/Manila')->toDateString()
        );
        $daily = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $records = $grouped->get($day->toDateString(), collect());
            $daily[] = ['date' => $day->toDateString(), 'revenue' => (float) $records->sum('total_amount'), 'orders' => $records->count()];
        }
        $recent = (clone $sales)->withSum('items as units', 'quantity')
            ->orderByDesc('updated_at')->orderByDesc('order_id')->limit(10)->get()
            ->map(fn ($order) => [
                'order_id' => $order->order_id,
                'sold_at' => $order->updated_at->toIso8601String(),
                'units' => (int) $order->units,
                'total' => (float) $order->total_amount,
            ]);

        return response()->json([
            'store_id' => (int) $storeId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'revenue' => $revenue, 'completed_orders' => $completed, 'units_sold' => (int) $units,
                'average_order_value' => $completed ? round($revenue / $completed, 2) : 0,
                'cancelled_orders' => $cancelled,
                'cancellation_rate' => $total ? round($cancelled / $total * 100, 2) : 0,
            ],
            'daily' => $daily, 'recent_sales' => $recent,
        ])->header('Cache-Control', 'no-store');
    }

    public function performance(Request $request)
    {
        $request->validate(['metric' => 'sometimes|in:revenue,completed_orders']);
        [$start, $end] = $this->dateRange($request);
        $metric = $request->input('metric', 'revenue');
        $sales = Order::where('status', 'picked_up')
            ->whereBetween('updated_at', [$start->copy()->utc(), $end->copy()->endOfDay()->utc()])
            ->select('store_id')->selectRaw('SUM(total_amount) as revenue, COUNT(*) as completed_orders')
            ->groupBy('store_id');
        // Aggregate before joining so each order contributes once. The left join keeps zero-sale stores.
        $stores = Store::query()
            ->whereHas('approvalStatus', fn ($q) => $q->where('status', 'approved'))
            ->whereHas('owner', fn ($q) => $q->where('role', 'Vendor'))
            ->leftJoinSub($sales, 'sales', 'stores.store_id', '=', 'sales.store_id')
            ->with('owner:user_id,full_name,account_status')
            ->select('stores.store_id', 'stores.store_name', 'stores.owner_id')
            ->selectRaw('COALESCE(sales.revenue, 0) as revenue, COALESCE(sales.completed_orders, 0) as completed_orders')
            ->get()->map(fn ($store) => [
                'store_id' => $store->store_id, 'store_name' => $store->store_name,
                'owner_name' => $store->owner->full_name, 'account_status' => $store->owner->account_status,
                'revenue' => (float) $store->revenue, 'completed_orders' => (int) $store->completed_orders,
            ])->sort(fn ($a, $b) => ($b[$metric] <=> $a[$metric]) ?: ($a['store_id'] <=> $b['store_id']))->values();
        $rank = 0;
        $previous = null;
        $ranked = $stores->map(function ($store, $index) use ($metric, &$rank, &$previous) {
            if ($previous === null || $previous != $store[$metric]) {
                $rank = $index + 1;
            }
            $previous = $store[$metric];
            return [...$store, 'rank' => $rank];
        });
        $least = $ranked->sort(fn ($a, $b) => ($a[$metric] <=> $b[$metric]) ?: ($a['store_id'] <=> $b['store_id']));
        return response()->json([
            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
            'generated_at' => now()->toIso8601String(), 'metric' => $metric,
            'eligible_stores' => $ranked->count(),
            'stores_without_sales' => $ranked->where('completed_orders', 0)->count(),
            'top' => $ranked->filter(fn ($store) => $store[$metric] > 0)->take(5)->values(),
            'least' => $least->take(5)->values(),
        ])->header('Cache-Control', 'no-store');
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d|required_with:end_date',
            'end_date' => 'nullable|date_format:Y-m-d|required_with:start_date|after_or_equal:start_date',
        ]);
        $end = Carbon::parse($request->input('end_date') ?: now('Asia/Manila')->toDateString(), 'Asia/Manila')->startOfDay();
        $start = $request->filled('start_date')
            ? Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay()
            : $end->copy()->subDays(29);
        abort_if($start->diffInDays($end) > 92, 422, 'Select a range of at most 93 days.');
        return [$start, $end];
    }
}
