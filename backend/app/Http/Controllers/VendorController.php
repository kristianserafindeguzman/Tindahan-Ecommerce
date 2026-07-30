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
        // For now, return 0 for everything since orders are not fully implemented.
        return response()->json([
            'placed_orders' => 0,
            'preparing_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'recent_orders' => []
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
