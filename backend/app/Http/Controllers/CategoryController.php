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

    /**
     * Update an existing category description.
     *
     * PATCH /api/categories/{id}
     */
    public function update(Request $request, $id)
    {
        $category = \App\Models\Category::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        if (array_key_exists('description', $validated)) {
            $category->description = $validated['description'];
            $category->save();
        }

        return response()->json([
            'message' => 'Category description updated successfully.',
            'category' => $category
        ]);
    }
}
