<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\Request;

class SearchLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'search_query' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,category_id',
            'search_lat' => 'required|numeric|between:-90,90',
            'search_lng' => 'required|numeric|between:-180,180',
        ]);

        $searchLog = SearchLog::create([
            'consumer_id' => $request->user()->user_id,
            'category_id' => $validated['category_id'],
            'search_query' => $validated['search_query'],
            'search_lat' => $validated['search_lat'],
            'search_lng' => $validated['search_lng'],
            'searched_at' => now(),
        ]);

        return response()->json([
            'message' => 'Search logged successfully.',
            'search_log' => $searchLog,
        ], 201);
    }
}