<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Get all categories.
     *
     * GET /api/categories
     */
    public function index()
    {
        $categories = DB::table('categories')
            ->select('category_id', 'category_name', 'description')
            ->orderBy('category_name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Create a new global category.
     *
     * POST /api/categories
     */
    public function store(Request $request)
    {
        // Skeleton logic for now. 
        // Validates and simulates a successful creation response.
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return response()->json([
            'message' => 'Category added successfully (Skeleton)'
        ], 201);
    }
}
