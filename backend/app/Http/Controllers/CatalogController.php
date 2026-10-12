<?php

namespace App\Http\Controllers;

use App\Models\ConsumerPersonalization;
use App\Models\Inventory;
use App\Models\LocalizedPopularSearch;
use App\Models\OrderItem;
use App\Models\SearchLog;
use App\Services\DistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    protected const ML_WEIGHT = 0.35;
    protected const RECENCY_WEIGHT = 0.30;
    protected const FREQUENCY_WEIGHT = 0.35;
    protected const RECENCY_LOOKBACK_DAYS = 30;
    protected const RECENCY_HALF_LIFE_DAYS = 7.0;


    /**
     * List active products from approved stores, for consumer browsing.
     *
     * GET /api/products
     */
    protected function getBaseCatalog(Request $request, DistanceService $distanceService, $isPersonalized = false)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $query = Inventory::with(['store', 'category'])

        // Optimized -gelo
        // $query = Inventory::with([
        //     'store',
        //     'category',
        //     'variants',
        // ])

            ->where('status', 'active')
            ->whereHas('store', function ($query) {
                $query->whereHas('approvalStatus', function ($query) {
                    $query->where('status', 'approved');
                });
            });

        if ($isPersonalized) {
            // Enforce available_quantity > 0 for personalized feed
            $query->whereRaw('stock_quantity - reserved_quantity > 0');
        }
        else {
            // Keep public catalog randomized

            // Remove to optimize -gelo
            $query->inRandomOrder();
        }

        return $query->get()
            ->map(function ($item) use ($lat, $lng, $distanceService) {
                $distance = null;
                if ($lat !== null && $lng !== null && $item->store) {
                    $distance = $distanceService->calculateDistance($lat, $lng, $item->store->latitude, $item->store->longitude);
                }

                return [
                    'id' => $item->inventory_id,
                    'name' => $item->product_name,
                    'description' => $item->description,
                    'category' => $item->category->category_name ?? null,
                    'category_id' => $item->category_id, // Needed for personalization ranking
                    'price' => (float) $item->price,
                    'image' => $item->image_url,
                    'store' => $item->store->store_name ?? null,
                    'storeId' => $item->store_id,
                    'inStock' => $item->available_quantity > 0,
                    'availableQuantity' => $item->available_quantity,
                    'variants' => $item->variants,
                    'distance_meters' => $distance,
                    'expiration_date' => $item->expiration_date ? $item->expiration_date->format('Y-m-d') : null,
                ];
            });
    }

    public function products(Request $request, DistanceService $distanceService)
    {
        $products = $this->getBaseCatalog($request, $distanceService, false);

        // If consumer coordinates exist, sort products by nearest store distance first
        if ($request->query('lat') !== null && $request->query('lng') !== null) {
            $products = $products->sortBy('distance_meters', SORT_REGULAR, false)->values();
        }

        return response()->json($products);
    }

    public function personalizedFeed(Request $request, DistanceService $distanceService)
    {
        $products = $this->getBaseCatalog($request, $distanceService, true);
        $consumerId = $request->user()->user_id;

        // Check if consumer has any search history
        $hasHistory = SearchLog::where('consumer_id', $consumerId)->exists();

        $fallbackCategories = [];
        $isFallback = false;

        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!$hasHistory) {
            // PATH B: NEW CONSUMER FALLBACK
            $isFallback = true;

            if ($lat !== null && $lng !== null) {
                // 1. Localized Popular Searches
                $latGrid = round((float)$lat, 2);
                $lngGrid = round((float)$lng, 2);

                $fallbackCategories = LocalizedPopularSearch::where('lat_grid', $latGrid)
                    ->where('lng_grid', $lngGrid)
                    ->whereNotNull('category_id')
                    ->orderBy('search_count', 'desc')
                    ->pluck('category_id')
                    ->unique()
                    ->toArray();

                // 2. Top-Selling Nearby Products logic is implicitly handled by scoring later
                // We fetch the top categories from nearby sales
                $nearbyTopCategories = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.order_id')
                    ->join('inventory', 'order_items.inventory_id', '=', 'inventory.inventory_id')
                    ->join('stores', 'orders.store_id', '=', 'stores.store_id')
                    ->where('orders.status', 'picked_up')
                    // Simple bounding box for "nearby" (~5km rough approx)
                    ->whereBetween('stores.latitude', [$lat - 0.05, $lat + 0.05])
                    ->whereBetween('stores.longitude', [$lng - 0.05, $lng + 0.05])
                    ->groupBy('inventory.category_id')
                    ->orderByRaw('SUM(order_items.quantity) DESC')
                    ->limit(5)
                    ->pluck('inventory.category_id')
                    ->toArray();

                // Merge localized search and nearby top sales
                $fallbackCategories = array_values(array_unique(array_merge($fallbackCategories, $nearbyTopCategories)));
            }

            // Fallback ranking: preserve existing deterministic ranking
            $products = $products->sortBy(function ($product) use ($fallbackCategories) {
                $catScore = 1000;
                $catId = $product['category_id'];

                if (in_array($catId, $fallbackCategories)) {
                    $catScore = array_search($catId, $fallbackCategories); // 0 is best
                }

                return [
                    $catScore,
                    $product['distance_meters'] ?? 999999,
                    $product['id']
                ];
            })->values();

            return response()->json([
                'is_fallback' => $isFallback,
                'products' => $products
            ]);
        }

        // PATH A: PERSONALIZED FEED FOR CONSUMERS WITH HISTORY
        // Check for latest successfully logged search category to prioritize
        $latestSearchLog = SearchLog::where('consumer_id', $consumerId)
            ->orderBy('searched_at', 'desc')
            ->orderBy('log_id', 'desc')
            ->first();

        $latestCategoryId = $latestSearchLog
            ? $this->resolveLatestSearchCategoryId($latestSearchLog, $products)
            : null;

        // Select a relevant available product from the latest search category with refresh variety
        $seed = $request->query('seed') ?? $request->query('_t');
        $priorityProduct = ($latestCategoryId !== null)
            ? $this->selectFirstRecommendationForCategory($latestCategoryId, $products, $latestSearchLog->search_query, $seed)
            : null;

        // Exclude priority product from remaining catalog so it is not duplicated in subsequent ranking
        $catalogForScoring = $priorityProduct
            ? $products->filter(fn($p) => $p['id'] !== $priorityProduct['id'])->values()
            : $products;

        // 1. ML PRECOMPUTED SCORES (Random Forest predicted future searches)
        $rawMlScores = ConsumerPersonalization::where('consumer_id', $consumerId)
            ->pluck('predicted_future_searches', 'category_id')
            ->toArray();

        $mlScores = [];
        foreach ($rawMlScores as $catId => $score) {
            $numericScore = (float) $score;
            if ($numericScore > 0) {
                $mlScores[(int) $catId] = $numericScore;
            }
        }
        $maxMl = !empty($mlScores) ? max($mlScores) : 0.0;

        // 2. REAL-TIME SEARCH OVERLAY (Last 30 days)
        $catalogCategoryIds = array_filter(array_unique(array_map(
            fn($p) => $p['category_id'] !== null ? (int) $p['category_id'] : null,
            is_array($products) ? $products : $products->all()
        )));

        $recentLogs = SearchLog::where('consumer_id', $consumerId)
            ->where('searched_at', '>=', now()->subDays(self::RECENCY_LOOKBACK_DAYS))
            ->get();

        $catCounts = [];
        $catLatest = [];

        foreach ($recentLogs as $log) {
            $catId = $log->category_id !== null ? (int) $log->category_id : null;
            // If the search log has no category_id or its category has 0 available products in catalog,
            // re-resolve from query against available catalog products
            if ($catId === null || !in_array($catId, $catalogCategoryIds, true)) {
                $catId = $this->resolveLatestSearchCategoryId($log, $products);
            }
            if ($catId !== null) {
                $catCounts[$catId] = ($catCounts[$catId] ?? 0) + 1;
                $searchedAt = $log->searched_at ? Carbon::parse($log->searched_at) : null;
                if ($searchedAt && (!isset($catLatest[$catId]) || $searchedAt->gt($catLatest[$catId]))) {
                    $catLatest[$catId] = $searchedAt;
                }
            }
        }

        $realtimeStats = collect();
        foreach ($catCounts as $cId => $cnt) {
            $realtimeStats->put($cId, (object) [
                'category_id' => $cId,
                'search_count' => $cnt,
                'latest_search' => isset($catLatest[$cId]) ? $catLatest[$cId]->toDateTimeString() : null,
            ]);
        }

        // 3. COMPOSITE SCORING (35% ML, 30% Recency, 35% Frequency)
        $categoryScores = $this->computeCompositeCategoryScores($mlScores, $realtimeStats);

        // 4. SCORE-AWARE CATEGORY INTERLEAVING
        $catalogArray = is_array($catalogForScoring) ? $catalogForScoring : $catalogForScoring->all();
        $interleaved = $this->interleaveCategoryProducts($catalogArray, $categoryScores, $priorityProduct);

        // Place priority product at position #1, followed by interleaved recommendations
        $finalProducts = $priorityProduct
            ? array_merge([$priorityProduct], $interleaved)
            : $interleaved;

        $products = collect($finalProducts)->values();

        return response()->json([
            'is_fallback' => $isFallback,
            'products' => $products
        ])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
    }

    /**
     * Resolves the category ID of the consumer's latest search log.
     */
    protected function resolveLatestSearchCategoryId(SearchLog $latestSearch, $availableProducts): ?int
    {
        // 1. If category_id is set and has available products in catalog, use it
        if ($latestSearch->category_id !== null) {
            $catId = (int) $latestSearch->category_id;
            foreach ($availableProducts as $p) {
                if ($p['category_id'] !== null && (int) $p['category_id'] === $catId) {
                    return $catId;
                }
            }
        }

        // 2. Otherwise, resolve from search_query against available products in catalog
        $query = trim((string) $latestSearch->search_query);
        if ($query === '') {
            return null;
        }

        $needle = mb_strtolower($query, 'UTF-8');

        // Check if query directly matches a known category name with available products
        foreach ($availableProducts as $p) {
            if (!empty($p['category']) && mb_strtolower(trim($p['category']), 'UTF-8') === $needle) {
                return (int) $p['category_id'];
            }
        }

        // Check if query matches word boundary in product name (e.g. "paper" matches "Bond Paper")
        foreach ($availableProducts as $p) {
            if ($p['category_id'] !== null) {
                $pName = mb_strtolower(trim($p['name']), 'UTF-8');
                if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($needle, '/') . '([^\p{L}\p{N}]|$)/ui', $pName)) {
                    return (int) $p['category_id'];
                }
            }
        }

        // Check if query matches a product name substring and borrow its category_id
        foreach ($availableProducts as $p) {
            if ($p['category_id'] !== null && mb_stripos($p['name'], $needle, 0, 'UTF-8') !== false) {
                return (int) $p['category_id'];
            }
        }

        return null;
    }

    /**
     * Selects a relevant available product from the prioritized latest search category,
     * providing reasonable variety across page refreshes while preserving distance and stock availability.
     */
    protected function selectFirstRecommendationForCategory(int $categoryId, $availableProducts, ?string $searchQuery = null, $seed = null): ?array
    {
        $categoryProducts = [];
        foreach ($availableProducts as $p) {
            if ($p['category_id'] !== null && (int) $p['category_id'] === $categoryId) {
                $categoryProducts[] = $p;
            }
        }

        if (empty($categoryProducts)) {
            return null;
        }

        $needle = $searchQuery !== null ? mb_strtolower(trim($searchQuery), 'UTF-8') : '';

        // Determine baseline closest distance
        $minDist = PHP_FLOAT_MAX;
        foreach ($categoryProducts as $p) {
            $dist = $p['distance_meters'] ?? 999999;
            if ($dist < $minDist) {
                $minDist = $dist;
            }
        }

        // Score candidates based on relevance to query and distance proximity
        $scoredCandidates = [];
        foreach ($categoryProducts as $p) {
            $pName = mb_strtolower(trim($p['name']), 'UTF-8');
            $relevanceScore = 10; // Base score for belonging to the prioritized category

            if ($needle !== '') {
                if ($pName === $needle) {
                    $relevanceScore = 100;
                } elseif (str_starts_with($pName, $needle) || str_starts_with($needle, $pName)) {
                    $relevanceScore = 70;
                } elseif (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($needle, '/') . '([^\p{L}\p{N}]|$)/ui', $pName)) {
                    $relevanceScore = 50;
                } elseif (mb_stripos($pName, $needle, 0, 'UTF-8') !== false) {
                    $relevanceScore = 30;
                }
            }

            $dist = $p['distance_meters'] ?? 999999;
            $distancePenalty = max(0, $dist - $minDist) / 100.0;
            $rankScore = $relevanceScore - $distancePenalty;

            $scoredCandidates[] = [
                'rank_score' => $rankScore,
                'distance' => $dist,
                'id' => $p['id'],
                'store_id' => $p['storeId'] ?? $p['id'],
                'product' => $p,
            ];
        }

        usort($scoredCandidates, function ($a, $b) {
            if (abs($a['rank_score'] - $b['rank_score']) > 1e-4) {
                return $b['rank_score'] <=> $a['rank_score'];
            }
            if ($a['distance'] !== $b['distance']) {
                return $a['distance'] <=> $b['distance'];
            }
            return $a['id'] <=> $b['id'];
        });

        // Filter candidates within a reasonable score/proximity band of the top candidate
        // to balance store/product variety without selecting distant or irrelevant listings.
        $topScore = $scoredCandidates[0]['rank_score'];
        $viableCandidates = array_filter($scoredCandidates, function ($c) use ($topScore) {
            return ($topScore - $c['rank_score']) <= 25.0;
        });
        if (empty($viableCandidates)) {
            $viableCandidates = $scoredCandidates;
        }

        // Group viable candidates by store
        $storeGroups = [];
        $storeBestScore = [];
        foreach ($viableCandidates as $cand) {
            $sId = $cand['store_id'];
            $storeGroups[$sId][] = $cand;
            if (!isset($storeBestScore[$sId]) || $cand['rank_score'] > $storeBestScore[$sId]) {
                $storeBestScore[$sId] = $cand['rank_score'];
            }
        }

        // Sort stores by their best candidate's rank score DESC
        uasort($storeBestScore, fn($a, $b) => $b <=> $a);

        // Round-robin alternate across stores to select distinct products and stores
        $topPool = [];
        $seenProductIds = [];
        $maxPoolSize = min(4, count($viableCandidates));
        $storePointers = array_fill_keys(array_keys($storeGroups), 0);

        while (count($topPool) < $maxPoolSize) {
            $addedInRound = false;
            foreach (array_keys($storeBestScore) as $sId) {
                $ptr = $storePointers[$sId];
                if ($ptr < count($storeGroups[$sId])) {
                    $candidate = $storeGroups[$sId][$ptr];
                    $storePointers[$sId]++;
                    $pId = $candidate['product']['id'];
                    if (!isset($seenProductIds[$pId])) {
                        $topPool[] = $candidate['product'];
                        $seenProductIds[$pId] = true;
                        $addedInRound = true;
                    }
                    if (count($topPool) >= $maxPoolSize) {
                        break 2;
                    }
                }
            }
            if (!$addedInRound) {
                break;
            }
        }

        // Fill remaining slots from viable candidates if needed
        if (count($topPool) < $maxPoolSize) {
            foreach ($viableCandidates as $cand) {
                $pId = $cand['product']['id'];
                if (!isset($seenProductIds[$pId])) {
                    $topPool[] = $cand['product'];
                    $seenProductIds[$pId] = true;
                }
                if (count($topPool) >= $maxPoolSize) {
                    break;
                }
            }
        }

        if (empty($topPool)) {
            return $categoryProducts[0];
        }

        // Select candidate using seed / timestamp to introduce variety across refreshes
        $poolSize = count($topPool);
        if ($seed !== null && is_numeric($seed)) {
            $idx = abs((int) $seed) % $poolSize;
        } else {
            $idx = mt_rand(0, $poolSize - 1);
        }

        return $topPool[$idx];
    }

    /**
     * Orders a category bucket balancing distance and store variety,
     * so that nearby stores alternate listings rather than one store taking all initial slots.
     */
    protected function orderBucketWithStoreVariety(array $bucket): array
    {
        if (count($bucket) <= 1) {
            return $bucket;
        }

        // Group by store_id
        $byStore = [];
        $storeDistances = [];

        foreach ($bucket as $p) {
            $storeKey = $p['storeId'] ?? $p['id'];
            $byStore[$storeKey][] = $p;
            $dist = $p['distance_meters'] ?? 999999;
            if (!isset($storeDistances[$storeKey]) || $dist < $storeDistances[$storeKey]) {
                $storeDistances[$storeKey] = $dist;
            }
        }

        // Sort items inside each store list by distance and id
        foreach ($byStore as $sId => &$items) {
            usort($items, function ($a, $b) {
                $distA = $a['distance_meters'] ?? 999999;
                $distB = $b['distance_meters'] ?? 999999;
                if ($distA !== $distB) {
                    return $distA <=> $distB;
                }
                return $a['id'] <=> $b['id'];
            });
        }
        unset($items);

        // Sort stores by their minimum distance
        uasort($storeDistances, fn($a, $b) => $a <=> $b);

        // Round-robin alternate across stores in distance order
        $ordered = [];
        $pointers = array_fill_keys(array_keys($byStore), 0);
        $totalItems = count($bucket);

        while (count($ordered) < $totalItems) {
            $added = false;
            foreach (array_keys($storeDistances) as $sId) {
                $ptr = $pointers[$sId];
                if ($ptr < count($byStore[$sId])) {
                    $ordered[] = $byStore[$sId][$ptr];
                    $pointers[$sId]++;
                    $added = true;
                }
            }
            if (!$added) {
                break;
            }
        }

        return $ordered;
    }

    /**
     * Computes composite scores for categories based on ML predictions, search recency, and frequency.
     */
    protected function computeCompositeCategoryScores(array $mlScores, $realtimeStats, ?\DateTimeInterface $now = null): array
    {
        $now = $now ?? now();
        $maxMl = !empty($mlScores) ? max($mlScores) : 0.0;
        $maxFreq = $realtimeStats ? ($realtimeStats->max('search_count') ?: 0) : 0;

        $statKeys = $realtimeStats ? $realtimeStats->keys()->map(fn($id) => (int) $id)->toArray() : [];
        $allCategoryIds = array_unique(array_merge(
            array_keys($mlScores),
            $statKeys
        ));

        $categoryScores = [];

        foreach ($allCategoryIds as $catId) {
            // ML Signal (35%)
            $mlNorm = ($maxMl > 0 && isset($mlScores[$catId]))
                ? ($mlScores[$catId] / $maxMl)
                : 0.0;

            // Recency Signal (30%) - 7-day half-life decay preserving recently frequent categories
            $stat = $realtimeStats ? $realtimeStats->get($catId) : null;
            $recencyFactor = 0.0;
            $freqNorm = 0.0;

            if ($stat) {
                $latestVal = is_object($stat) ? ($stat->latest_search ?? null) : ($stat['latest_search'] ?? null);
                $latest = Carbon::parse($latestVal);
                $daysSince = max(0, $now->diffInSeconds($latest)) / 86400.0;
                $recencyFactor = self::RECENCY_HALF_LIFE_DAYS / (self::RECENCY_HALF_LIFE_DAYS + $daysSince);

                // Frequency Signal (35%) - Normalized search volume in the lookback window
                $searchCount = is_object($stat) ? ($stat->search_count ?? 0) : ($stat['search_count'] ?? 0);
                $freqNorm = ($maxFreq > 0)
                    ? ((int) $searchCount / (float) $maxFreq)
                    : 0.0;
            }

            $score = (self::ML_WEIGHT * $mlNorm)
                   + (self::RECENCY_WEIGHT * $recencyFactor)
                   + (self::FREQUENCY_WEIGHT * $freqNorm);

            if ($score > 0) {
                $categoryScores[$catId] = $score;
            }
        }

        arsort($categoryScores);
        return $categoryScores;
    }

    /**
     * Interleaves scored categories using score-proportional block emission,
     * organizing items in each category bucket with store variety.
     *
     * Each category receives a consecutive block of products whose length is
     * proportional to its composite score share. This keeps the feed focused on
     * the consumer's strongest interests (no forced alternation) while ensuring
     * lower-ranked categories remain available further down.
     */
    protected function interleaveCategoryProducts(array $products, array $categoryScores, ?array $priorityProduct = null): array
    {
        $scoredBuckets = [];
        $unscoredProducts = [];

        foreach ($products as $product) {
            $catId = $product['category_id'] !== null ? (int) $product['category_id'] : null;
            if ($catId !== null && isset($categoryScores[$catId])) {
                $scoredBuckets[$catId][] = $product;
            } else {
                $unscoredProducts[] = $product;
            }
        }

        // Organize products within each scored bucket balancing distance and store variety
        foreach ($scoredBuckets as $catId => &$bucket) {
            $bucket = $this->orderBucketWithStoreVariety($bucket);
        }
        unset($bucket);

        // Sort unscored products by distance
        usort($unscoredProducts, function ($a, $b) {
            $distA = $a['distance_meters'] ?? 999999;
            $distB = $b['distance_meters'] ?? 999999;
            if ($distA == $distB) {
                return $a['id'] <=> $b['id'];
            }
            return $distA <=> $distB;
        });

        // Build the category ordering: descending by composite score
        $activeCatIds = [];
        foreach ($categoryScores as $catId => $score) {
            if (!empty($scoredBuckets[$catId])) {
                $activeCatIds[] = $catId;
            }
        }

        if (empty($activeCatIds)) {
            return array_merge([], $unscoredProducts);
        }

        // Compute score-proportional block sizes for one round.
        // Each category gets at least 1 slot per round; the minimum round size
        // equals the number of active categories.
        $totalScore = 0.0;
        foreach ($activeCatIds as $catId) {
            $totalScore += $categoryScores[$catId];
        }

        $interleavedScored = [];
        $pointers = array_fill_keys($activeCatIds, 0);

        // If a priority product was prepended, count it towards its category's first block
        $priorityCatId = ($priorityProduct && $priorityProduct['category_id'] !== null)
            ? (int) $priorityProduct['category_id']
            : null;
        $priorityAlreadyEmitted = ($priorityCatId !== null) ? 1 : 0;

        while (!empty($activeCatIds)) {
            // Each round: allocate a block of products per category proportional to its score.
            // The round size scales with the number of active categories so that a dominant
            // category naturally gets more consecutive slots (e.g. scores 0.95 vs 0.20 with
            // round multiplier of 6 → Cat A gets 5 slots, Cat B gets 1).
            $roundMultiplier = max(count($activeCatIds) * 3, 6);

            foreach ($activeCatIds as $catId) {
                $remaining = count($scoredBuckets[$catId]) - $pointers[$catId];
                if ($remaining <= 0) continue;

                // Proportional block: score / totalScore * roundMultiplier, at least 1
                $blockSize = ($totalScore > 0)
                    ? (int) round(($categoryScores[$catId] / $totalScore) * $roundMultiplier)
                    : 1;
                $blockSize = max(1, $blockSize);

                // Deduct the priority product from the first block of its category
                if ($priorityAlreadyEmitted > 0 && $catId === $priorityCatId) {
                    $blockSize = max(0, $blockSize - $priorityAlreadyEmitted);
                    $priorityAlreadyEmitted = 0; // Only deduct once
                }

                $blockSize = min($blockSize, $remaining);

                for ($j = 0; $j < $blockSize; $j++) {
                    $interleavedScored[] = $scoredBuckets[$catId][$pointers[$catId]];
                    $pointers[$catId]++;
                }
            }

            // Remove exhausted categories and recalculate total score for next round
            $activeCatIds = array_values(array_filter($activeCatIds, function ($catId) use ($scoredBuckets, $pointers) {
                return $pointers[$catId] < count($scoredBuckets[$catId]);
            }));

            $totalScore = 0.0;
            foreach ($activeCatIds as $catId) {
                $totalScore += $categoryScores[$catId];
            }
        }

        return array_merge($interleavedScored, $unscoredProducts);
    }
}
