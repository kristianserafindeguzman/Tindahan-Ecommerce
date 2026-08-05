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
                'picked_up_orders' => 0,
                'cancelled_orders' => 0,
                'recent_orders' => []
            ]);
        }
        
        $storeId = $user->store->store_id;

        $placed = \App\Models\Order::where('store_id', $storeId)->where('status', 'placed')->count();
        $preparing = \App\Models\Order::where('store_id', $storeId)->where('status', 'preparing')->count();
        $picked_up = \App\Models\Order::where('store_id', $storeId)->where('status', 'picked_up')->count();
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
            'picked_up_orders' => $picked_up,
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

    /**
     * Get data for interactive revenue chart.
     *
     * GET /api/vendor/stats/chart
     */
    public function chartStats(Request $request)
    {
        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json([]);
        }
        $storeId = $vendor->store->store_id;
        $filter = $request->query('filter', 'Daily');

        $orders = \App\Models\Order::where('store_id', $storeId)
            ->where('status', 'picked_up')
            ->get();
            
        $data = [];

        if (strtolower($filter) === 'weekly') {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->startOfWeek()->format('M d, Y');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        } elseif (strtolower($filter) === 'monthly') {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->format('M Y');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        } else {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->format('M d');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        }

        // Sort chronologically using Carbon parse on period
        usort($data, function($a, $b) {
            return \Carbon\Carbon::parse($a['period'])->timestamp <=> \Carbon\Carbon::parse($b['period'])->timestamp;
        });

        // Limit results for visualization
        if (strtolower($filter) === 'weekly') {
            $data = array_slice($data, -8); // last 8 weeks
        } elseif (strtolower($filter) === 'monthly') {
            $data = array_slice($data, -6); // last 6 months
        } else {
            $data = array_slice($data, -7); // last 7 days
        }

        return response()->json(array_values($data));
    }

    /**
     * Upload and update the store image.
     *
     * POST /api/vendor/profile/store-image
     */
    public function uploadStoreImage(Request $request)
    {
        $request->validate([
            'store_picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $request->user();
        if (!$user->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $store = $user->store;

        if ($request->hasFile('store_picture')) {
            $photoPath = $request->file('store_picture')->store('stores', 'public');

            // Update the store record with raw path to match registration
            $store->store_picture = $photoPath;
            $store->save();

            $storeUrl = asset('storage/' . $photoPath);

            return response()->json([
                'message' => 'Store image uploaded successfully.',
                'store_picture' => $storeUrl
            ]);
        }

        return response()->json(['message' => 'No image uploaded.'], 400);
    }
}
