<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;

class VendorController extends Controller
{
    /**
     * Get vendor dashboard statistics.
     *
     * GET /api/vendor/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        if (!$user->store) {
            return response()->json([
                'placed_orders' => 0,
                'preparing_orders' => 0,
                'completed_orders' => 0,
                'cancelled_orders' => 0,
                'recent_orders' => []
            ]);
        }
        
        $storeId = $user->store->store_id;

        $placed = \App\Models\Order::where('store_id', $storeId)->where('status', 'placed')->count();
        $preparing = \App\Models\Order::where('store_id', $storeId)->where('status', 'preparing')->count();
        $completed = \App\Models\Order::where('store_id', $storeId)->where('status', 'picked_up')->count();
        $cancelled = \App\Models\Order::where('store_id', $storeId)->where('status', 'cancelled')->count();

        $recentOrders = \App\Models\Order::with('consumer')
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->order_id,
                    'date' => $order->created_at->format('M j, Y, g:i a'),
                    'customer' => $order->consumer->full_name ?? 'Unknown',
                    'avatar' => $order->consumer->profile_picture_url ?? null,
                    'price' => '₱' . number_format($order->total_amount, 2),
                    'status' => ucfirst(str_replace('_', ' ', $order->status))
                ];
            });

        return response()->json([
            'placed_orders' => $placed,
            'preparing_orders' => $preparing,
            'completed_orders' => $completed,
            'cancelled_orders' => $cancelled,
            'recent_orders' => $recentOrders
        ]);
    }

    /**
     * Get the authenticated vendor's profile and store information.
     *
     * GET /api/vendor/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->load('store');
        }
        
        return response()->json($user);
    }
}
