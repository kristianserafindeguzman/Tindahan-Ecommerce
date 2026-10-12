<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $consumerId = $request->user()->user_id;
        $query = trim($validated['search_query']);

        // Reject stringified JavaScript objects (e.g. "[object KeyboardEvent]") that indicate
        // the frontend leaked an Event into the query parameter instead of the actual search text.
        if (preg_match('/^\[object\s+\w+\]$/i', $query)) {
            return response()->json([
                'message' => 'Invalid search query.',
                'errors' => ['search_query' => ['Search query contains an invalid value.']],
            ], 422);
        }

        // Prevent duplicate logs from rapid resubmissions within 2 seconds
        $existingLog = SearchLog::where('consumer_id', $consumerId)
            ->where('search_query', $query)
            ->where('searched_at', '>=', now()->subSeconds(2))
            ->first();

        if ($existingLog) {
            return response()->json([
                'message' => 'Search logged successfully.',
                'search_log' => $existingLog,
            ], 200);
        }

        $categoryId = $validated['category_id'] ?? null;

        // If category is not provided or has no active inventory in the database,
        // resolve it from matching active inventory products and categories
        if ($categoryId === null || !Inventory::where('category_id', $categoryId)->where('status', 'active')->exists()) {
            if ($query !== '') {
                $resolved = $this->resolveCategoryFromDatabase($query);
                if ($resolved !== null) {
                    $categoryId = $resolved;
                }
            }
        }

        $searchLog = SearchLog::create([
            'consumer_id' => $consumerId,
            // A query that matches no category is still worth logging: Path B groups on the raw
            // query, and the column is nullable for exactly this case.
            'category_id' => $categoryId,
            'search_query' => $query,
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

    /**
     * Resolves the category ID from database inventory and category matches for a typed search query.
     */
    public function resolveCategoryFromDatabase(string $query, $products = null, $categories = null): ?int
    {
        $needle = mb_strtolower(trim($query), 'UTF-8');
        if ($needle === '') {
            return null;
        }

        // When collections are passed directly (e.g. unit tests or in-memory collections)
        if ($products !== null || $categories !== null) {
            return $this->resolveCategoryFromCollection($needle, $products ?? [], $categories ?? []);
        }

        // 1. Direct Category Name match (prefer category if it has active inventory)
        $exactCategory = Category::whereRaw('LOWER(category_name) = ?', [$needle])->value('category_id');
        if ($exactCategory !== null && Inventory::where('category_id', $exactCategory)->where('status', 'active')->exists()) {
            return (int) $exactCategory;
        }

        $categoryPrefixMatch = Category::whereRaw('LOWER(category_name) LIKE ?', ["{$needle}%"])->value('category_id');
        if ($categoryPrefixMatch !== null && Inventory::where('category_id', $categoryPrefixMatch)->where('status', 'active')->exists()) {
            return (int) $categoryPrefixMatch;
        }

        // 2. Inventory matches: exact product name
        $activeInventory = Inventory::where('status', 'active')
            ->whereNotNull('category_id');

        $exactProduct = (clone $activeInventory)
            ->whereRaw('LOWER(product_name) = ?', [$needle])
            ->value('category_id');
        if ($exactProduct !== null) {
            return (int) $exactProduct;
        }

        // 3. Inventory matches: word boundary or prefix match in product name (e.g. "paper" matches "Short Bond Paper")
        $wordMatch = (clone $activeInventory)
            ->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(product_name) LIKE ?', ["{$needle}%"])
                  ->orWhereRaw('LOWER(product_name) LIKE ?', ["% {$needle}%"])
                  ->orWhereRaw('LOWER(product_name) LIKE ?', ["%-{$needle}%"]);
            })
            ->select('category_id', DB::raw('COUNT(*) as match_count'))
            ->groupBy('category_id')
            ->orderByDesc('match_count')
            ->orderBy('category_id')
            ->first();

        if ($wordMatch !== null) {
            return (int) $wordMatch->category_id;
        }

        // 4. Inventory matches: substring in product name
        $substringMatch = (clone $activeInventory)
            ->whereRaw('LOWER(product_name) LIKE ?', ["%{$needle}%"])
            ->select('category_id', DB::raw('COUNT(*) as match_count'))
            ->groupBy('category_id')
            ->orderByDesc('match_count')
            ->orderBy('category_id')
            ->first();

        if ($substringMatch !== null) {
            return (int) $substringMatch->category_id;
        }

        // 5. Category description match (category descriptions list items belonging to the category)
        $categoryDescMatch = Category::whereNotNull('description')
            ->whereRaw('LOWER(description) LIKE ?', ["%{$needle}%"])
            ->value('category_id');

        if ($categoryDescMatch !== null) {
            return (int) $categoryDescMatch;
        }

        // 6. Inventory matches: substring in product description
        $productDescMatch = (clone $activeInventory)
            ->whereNotNull('description')
            ->whereRaw('LOWER(description) LIKE ?', ["%{$needle}%"])
            ->select('category_id', DB::raw('COUNT(*) as match_count'))
            ->groupBy('category_id')
            ->orderByDesc('match_count')
            ->orderBy('category_id')
            ->first();

        if ($productDescMatch !== null) {
            return (int) $productDescMatch->category_id;
        }

        if ($exactCategory !== null) {
            return (int) $exactCategory;
        }

        if ($categoryPrefixMatch !== null) {
            return (int) $categoryPrefixMatch;
        }

        return null;
    }

    /**
     * Resolves category from in-memory product and category collections.
     */
    protected function resolveCategoryFromCollection(string $needle, $products, $categories): ?int
    {
        // 1. Direct Category Name match
        foreach ($categories as $cat) {
            $catName = mb_strtolower(trim(is_array($cat) ? ($cat['category_name'] ?? $cat['label'] ?? '') : ($cat->category_name ?? $cat->label ?? '')), 'UTF-8');
            $catId = is_array($cat) ? ($cat['category_id'] ?? $cat['id'] ?? null) : ($cat->category_id ?? $cat->id ?? null);
            if ($catId !== null && ($catName === $needle || str_starts_with($catName, $needle))) {
                return (int) $catId;
            }
        }

        // 2. Exact product name match
        foreach ($products as $p) {
            $pName = mb_strtolower(trim(is_array($p) ? ($p['product_name'] ?? $p['name'] ?? '') : ($p->product_name ?? $p->name ?? '')), 'UTF-8');
            $catId = is_array($p) ? ($p['category_id'] ?? $p['categoryId'] ?? null) : ($p->category_id ?? $p->categoryId ?? null);
            if ($catId !== null && $pName === $needle) {
                return (int) $catId;
            }
        }

        // 3. Word-boundary match in product name
        $wordCounts = [];
        foreach ($products as $p) {
            $pName = mb_strtolower(trim(is_array($p) ? ($p['product_name'] ?? $p['name'] ?? '') : ($p->product_name ?? $p->name ?? '')), 'UTF-8');
            $catId = is_array($p) ? ($p['category_id'] ?? $p['categoryId'] ?? null) : ($p->category_id ?? $p->categoryId ?? null);
            if ($catId !== null) {
                if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($needle, '/') . '([^\p{L}\p{N}]|$)/ui', $pName)) {
                    $wordCounts[(int) $catId] = ($wordCounts[(int) $catId] ?? 0) + 1;
                }
            }
        }
        if (!empty($wordCounts)) {
            arsort($wordCounts);
            return (int) array_key_first($wordCounts);
        }

        // 4. Substring match in product name
        $subCounts = [];
        foreach ($products as $p) {
            $pName = mb_strtolower(trim(is_array($p) ? ($p['product_name'] ?? $p['name'] ?? '') : ($p->product_name ?? $p->name ?? '')), 'UTF-8');
            $catId = is_array($p) ? ($p['category_id'] ?? $p['categoryId'] ?? null) : ($p->category_id ?? $p->categoryId ?? null);
            if ($catId !== null && mb_stripos($pName, $needle, 0, 'UTF-8') !== false) {
                $subCounts[(int) $catId] = ($subCounts[(int) $catId] ?? 0) + 1;
            }
        }
        if (!empty($subCounts)) {
            arsort($subCounts);
            return (int) array_key_first($subCounts);
        }

        // 5. Category description match
        foreach ($categories as $cat) {
            $desc = mb_strtolower(trim(is_array($cat) ? ($cat['description'] ?? '') : ($cat->description ?? '')), 'UTF-8');
            $catId = is_array($cat) ? ($cat['category_id'] ?? $cat['id'] ?? null) : ($cat->category_id ?? $cat->id ?? null);
            if ($catId !== null && mb_stripos($desc, $needle, 0, 'UTF-8') !== false) {
                return (int) $catId;
            }
        }

        return null;
    }
}