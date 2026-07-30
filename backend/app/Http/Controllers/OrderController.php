<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get paginated orders for the vendor.
     *
     * GET /api/vendor/orders
     */
    public function index(Request $request)
    {
        // Skeleton: returning empty structure
        return response()->json([
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'total' => 0
        ]);
    }

    /**
     * Get unique customers for the vendor.
     *
     * GET /api/vendor/customers
     */
    public function customers(Request $request)
    {
        // Skeleton: returning empty array
        return response()->json([]);
    }

    /**
     * Get orders for a specific customer.
     *
     * GET /api/vendor/customers/{id}/orders
     */
    public function customerOrders(Request $request, $id)
    {
        // Skeleton: returning empty array
        return response()->json([]);
    }

    /**
     * Get details for a specific order.
     *
     * GET /api/vendor/orders/{id}
     */
    public function show(Request $request, $id)
    {
        // Skeleton: returning empty structure
        return response()->json(null);
    }
}
