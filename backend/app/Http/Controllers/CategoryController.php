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
            ->whereNull('store_id')
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
        $user = $request->user();
        $storeId = $user->role === 'Vendor' ? $user->store->store_id : null;

        $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('categories', 'category_name')->where(function ($query) use ($storeId) {
                    $query->whereNull('store_id')->orWhere('store_id', $storeId);
                })
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $category = \App\Models\Category::create([
            'store_id' => $storeId,
            'category_name' => $request->category_name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Category added successfully.',
            'category' => $category,
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
        $user = $request->user();

        if ($user->role === 'Vendor') {
            if ($category->store_id !== $user->store->store_id) {
                return response()->json(['message' => 'Unauthorized to edit this category.'], 403);
            }
        } elseif ($user->role === 'Admin') {
            if ($category->store_id !== null) {
                return response()->json(['message' => 'Admins can only edit global categories.'], 403);
            }
        }

        $validated = $request->validate([
            // Same 255 limit as store(), which the varchar(255) column enforces either way.
            'description' => 'nullable|string|max:255',
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

    /**
     * Delete a category that no product uses. Admin only (see routes/api.php).
     *
     * DELETE /api/categories/{id}
     */
    public function destroy(Request $request, $id)
    {
        $category = \App\Models\Category::findOrFail($id);
        $user = $request->user();

        if ($user->role === 'Vendor') {
            if ($category->store_id !== $user->store->store_id) {
                return response()->json(['message' => 'Unauthorized to delete this category.'], 403);
            }
        } elseif ($user->role === 'Admin') {
            if ($category->store_id !== null) {
                return response()->json(['message' => 'Admins can only delete global categories.'], 403);
            }
        }

        // inventory.category_id cascades on delete, so deleting a category that is still in use
        // would delete those products outright. The count deliberately spans every store and
        // includes archived products, because the vendor's own list only counts its active ones.
        $productCount = \App\Models\Inventory::where('category_id', $category->category_id)->count();

        if ($productCount > 0) {
            return response()->json([
                'message' => 'This category still has ' . $productCount . ' ' . ($productCount === 1 ? 'product' : 'products')
                    . ' in it. Move or delete them first, then delete the category.',
                'products_count' => $productCount,
            ], 409);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
