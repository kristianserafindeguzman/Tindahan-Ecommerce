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
            'category_id' => 'nullable|integer|exists:categories,category_id',
            // Optional: a consumer with no detected or saved address still gets their search
            // history recorded. Only the localized popular-search path needs coordinates.
            'search_lat' => 'nullable|numeric|between:-90,90',
            'search_lng' => 'nullable|numeric|between:-180,180',
        ]);

        $searchLog = SearchLog::create([
            'consumer_id' => $request->user()->user_id,
            // A query that matches no category is still worth logging: Path B groups on the raw
            // query, and the column is nullable for exactly this case.
            'category_id' => $validated['category_id'] ?? null,
            'search_query' => $validated['search_query'],
            // Stored as NULL rather than a stand-in coordinate, so nothing downstream mistakes
            // an unknown location for a real one.
            'search_lat' => $validated['search_lat'] ?? null,
            'search_lng' => $validated['search_lng'] ?? null,
            'searched_at' => now(),
        ]);

        return response()->json([
            'message' => 'Search logged successfully.',
            'search_log' => $searchLog,
        ], 201);
    }
}