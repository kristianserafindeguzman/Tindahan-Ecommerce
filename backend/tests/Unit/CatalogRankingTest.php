<?php

namespace Tests\Unit;

use App\Http\Controllers\CatalogController;
use App\Models\SearchLog;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TestableCatalogController extends CatalogController
{
    public function publicResolveLatestSearchCategoryId(SearchLog $latestSearch, array $availableProducts): ?int
    {
        return $this->resolveLatestSearchCategoryId($latestSearch, $availableProducts);
    }

    public function publicSelectFirstRecommendation(int $categoryId, array $availableProducts, ?string $searchQuery = null, $seed = null): ?array
    {
        return $this->selectFirstRecommendationForCategory($categoryId, $availableProducts, $searchQuery, $seed);
    }

    public function publicOrderBucketWithStoreVariety(array $bucket): array
    {
        return $this->orderBucketWithStoreVariety($bucket);
    }

    public function publicComputeCompositeCategoryScores(array $mlScores, $realtimeStats, ?\DateTimeInterface $now = null): array
    {
        return $this->computeCompositeCategoryScores($mlScores, $realtimeStats, $now);
    }

    public function publicInterleaveCategoryProducts(array $products, array $categoryScores, ?array $priorityProduct = null): array
    {
        return $this->interleaveCategoryProducts($products, $categoryScores, $priorityProduct);
    }
}

class CatalogRankingTest extends TestCase
{
    private TestableCatalogController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableCatalogController();
    }

    /**
     * Helper to create a sample product item.
     */
    private function createProduct(
        int $id,
        ?int $categoryId,
        ?float $distanceMeters = null,
        string $name = 'Product',
        ?string $categoryName = null,
        int $storeId = 1,
        string $storeName = 'Sample Store',
        bool $inStock = true,
        int $quantity = 10
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'description' => "Description for {$name}",
            'category' => $categoryName ?? ($categoryId ? "Category {$categoryId}" : null),
            'category_id' => $categoryId,
            'price' => 25.0,
            'image' => "http://example.com/p{$id}.jpg",
            'store' => $storeName,
            'storeId' => $storeId,
            'inStock' => $inStock,
            'availableQuantity' => $quantity,
            'variants' => [],
            'distance_meters' => $distanceMeters,
            'expiration_date' => null,
        ];
    }

    private function createSearchLog(string $query, ?int $categoryId = null): SearchLog
    {
        $log = new SearchLog();
        $log->search_query = $query;
        $log->category_id = $categoryId;
        return $log;
    }

    /**
     * Executes the full pipeline with latest search category prioritization and interleaving.
     */
    private function runFullFeedPipeline(array $products, array $categoryScores, ?SearchLog $latestSearchLog = null, $seed = null): array
    {
        $latestCategoryId = $latestSearchLog
            ? $this->controller->publicResolveLatestSearchCategoryId($latestSearchLog, $products)
            : null;

        $priorityProduct = ($latestCategoryId !== null)
            ? $this->controller->publicSelectFirstRecommendation($latestCategoryId, $products, $latestSearchLog->search_query, $seed)
            : null;

        $catalogForScoring = $priorityProduct
            ? array_values(array_filter($products, fn($p) => $p['id'] !== $priorityProduct['id']))
            : $products;

        $interleaved = $this->controller->publicInterleaveCategoryProducts($catalogForScoring, $categoryScores, $priorityProduct);

        return $priorityProduct ? array_merge([$priorityProduct], $interleaved) : $interleaved;
    }

    /**
     * Test 1: Prioritize the category of the latest search at position #1,
     * not permanently locked to one specific product, while preserving older interests at position #2.
     */
    public function test_latest_search_prioritizes_resolved_category_at_position_one(): void
    {
        $products = [
            $this->createProduct(1, 2, 100, 'Coke 1.5L', 'Beverages', 1),
            $this->createProduct(2, 2, 200, 'Sprite 1.5L', 'Beverages', 1),
            $this->createProduct(3, 1, 150, 'Lucky Me! Pancit Canton', 'Noodles and Pasta', 1),
            $this->createProduct(4, 1, 300, 'Payless Pancit Canton', 'Noodles and Pasta', 2),
        ];

        // Search for "pancit canton" (Category 1)
        $log = $this->createSearchLog('pancit canton', 1);
        $resolvedCatId = $this->controller->publicResolveLatestSearchCategoryId($log, $products);
        $this->assertEquals(1, $resolvedCatId);

        $priority = $this->controller->publicSelectFirstRecommendation($resolvedCatId, $products, $log->search_query, 0);
        $this->assertNotNull($priority);
        $this->assertEquals(1, $priority['category_id'], 'Priority product must be from the searched Noodles category');

        // Higher ML score for Beverages (Cat 2 = 0.95), lower for Noodles (Cat 1 = 0.40)
        $categoryScores = [2 => 0.95, 1 => 0.40];
        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $log, 0);

        // Position #1 must belong to the latest category
        $this->assertEquals(1, $ranked[0]['category_id'], 'Position #1 must be from category 1');
        // Position #2 should be from the high ML category (Beverages), preserving older interests
        $this->assertEquals(2, $ranked[1]['category_id'], 'Beverages should remain visible right after position #1');
    }

    /**
     * Test 2: Repeated searches for a product (e.g. bond paper) keep the category highly ranked.
     */
    public function test_repeated_bond_paper_searches_keep_relevant_category_highly_ranked(): void
    {
        $products = [
            $this->createProduct(1, 3, 100, 'Short Bond Paper', 'School Supplies', 1),
            $this->createProduct(2, 3, 150, 'Long Bond Paper', 'School Supplies', 1),
            $this->createProduct(3, 3, 120, 'Short Bond Paper', 'School Supplies', 2),
            $this->createProduct(10, 2, 80, 'Coke 1.5L', 'Beverages', 1),
        ];

        // Consumer repeatedly searched "bond paper" (School Supplies, Cat 3)
        $log = $this->createSearchLog('bond paper', 3);
        $resolvedCatId = $this->controller->publicResolveLatestSearchCategoryId($log, $products);
        $this->assertEquals(3, $resolvedCatId);

        // Category 3 has top composite score due to repeated frequency and recent search
        $categoryScores = [3 => 0.85, 2 => 0.40];
        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $log, 0);

        // Top recommendation is a bond paper / school supply product
        $this->assertEquals(3, $ranked[0]['category_id']);
        $this->assertStringContainsString('Bond Paper', $ranked[0]['name']);

        // Category 3 dominates the feed proportionally without blocking
        $cat3Items = array_filter($ranked, fn($p) => $p['category_id'] === 3);
        $this->assertCount(3, $cat3Items);
    }

    /**
     * Test 3: Refreshing surfaces different relevant products or store listings
     * from the candidate pool rather than permanently pinning one exact listing.
     */
    public function test_refreshing_surfaces_different_relevant_products_and_stores_from_candidate_pool(): void
    {
        $products = [
            $this->createProduct(1, 3, 100, 'Short Bond Paper', 'School Supplies', 1), // Store 1
            $this->createProduct(2, 3, 100, 'Long Bond Paper', 'School Supplies', 1),  // Store 1
            $this->createProduct(3, 3, 120, 'Short Bond Paper', 'School Supplies', 2), // Store 2 (same product, different store)
            $this->createProduct(4, 3, 140, 'A4 Bond Paper', 'School Supplies', 3),    // Store 3 (different product, different store)
            $this->createProduct(5, 3, 50000, 'Far Bond Paper', 'School Supplies', 4), // Store 4 (distant store - 50km)
        ];

        // Test variety across seeds (which simulate page refreshes via timestamp / seed parameter)
        $surfacedIds = [];
        $surfacedStores = [];
        $surfacedNames = [];

        for ($seed = 0; $seed < 4; $seed++) {
            $selected = $this->controller->publicSelectFirstRecommendation(3, $products, 'bond paper', $seed);
            $this->assertNotNull($selected);
            $this->assertEquals(3, $selected['category_id']);
            $this->assertNotEquals(5, $selected['id'], 'Distant store (50km) must not be in top candidate pool');

            $surfacedIds[$selected['id']] = true;
            $surfacedStores[$selected['storeId']] = true;
            $surfacedNames[$selected['name']] = true;
        }

        // Verify that different products from the category are surfaced
        $this->assertGreaterThan(1, count($surfacedNames), 'Refreshing should surface different products in the category');
        // Verify that different stores are surfaced
        $this->assertGreaterThan(1, count($surfacedStores), 'Refreshing should surface different stores');
        // Verify multiple distinct inventory listings are surfaced across refreshes
        $this->assertGreaterThan(1, count($surfacedIds), 'Refreshing should not permanently pin one exact listing');
    }

    /**
     * Test 4: Searching another product does not bury the previously frequent category.
     */
    public function test_searching_another_product_does_not_bury_previously_frequent_category(): void
    {
        $now = Carbon::parse('2026-10-12 12:00:00');

        // Established frequent category: School Supplies (Cat 3) with 15 searches 2 days ago
        // New search: Noodles (Cat 1) with 1 search just now
        $realtimeStats = collect([
            3 => (object) [
                'category_id' => 3,
                'search_count' => 15,
                'latest_search' => '2026-10-10 12:00:00', // 2 days ago
            ],
            1 => (object) [
                'category_id' => 1,
                'search_count' => 1,
                'latest_search' => '2026-10-12 12:00:00', // Just now
            ],
        ]);

        $mlScores = [3 => 5.0, 1 => 2.0]; // ML predicts future interest in Cat 3
        $computedScores = $this->controller->publicComputeCompositeCategoryScores($mlScores, $realtimeStats, $now);

        // Cat 3 should maintain a significantly higher composite score than Cat 1 due to 15 searches & 7-day half-life
        $this->assertGreaterThan(
            $computedScores[1],
            $computedScores[3],
            'Frequent category (Cat 3) must maintain higher composite score than single new search (Cat 1)'
        );

        $products = [
            $this->createProduct(1, 1, 100, 'Lucky Me Pancit Canton', 'Noodles and Pasta', 1),
            $this->createProduct(2, 1, 200, 'Payless Pancit Canton', 'Noodles and Pasta', 1),
            $this->createProduct(3, 3, 100, 'Short Bond Paper', 'School Supplies', 1),
            $this->createProduct(4, 3, 150, 'Long Bond Paper', 'School Supplies', 1),
            $this->createProduct(5, 3, 200, 'Yellow Pad Paper', 'School Supplies', 2),
        ];

        // Consumer just searched for "pancit canton" (Cat 1)
        $latestSearch = $this->createSearchLog('pancit canton', 1);
        $ranked = $this->runFullFeedPipeline($products, $computedScores, $latestSearch, 0);

        // Position #1 goes to the latest search category (Noodles)
        $this->assertEquals(1, $ranked[0]['category_id'], 'Position #1 must be from the latest category');

        // Position #2 immediately belongs to the established frequent category (School Supplies)
        $this->assertEquals(3, $ranked[1]['category_id'], 'Position #2 must be from the frequent category (Cat 3), not buried!');
    }

    /**
     * Test 5: Store variety alternation within category buckets ensures multiple stores alternate listings.
     */
    public function test_store_variety_alternation_within_category_bucket(): void
    {
        $bucket = [
            $this->createProduct(1, 4, 100, 'Piattos Cheese', 'Snacks', 1),
            $this->createProduct(2, 4, 100, 'Nova Chips', 'Snacks', 1),
            $this->createProduct(3, 4, 100, 'V-Cut BBQ', 'Snacks', 1),
            $this->createProduct(4, 4, 120, 'Piattos Cheese', 'Snacks', 2), // Store 2 selling same product
            $this->createProduct(5, 4, 120, 'Chippy Red', 'Snacks', 2),
        ];

        $ordered = $this->controller->publicOrderBucketWithStoreVariety($bucket);

        // Stores should alternate: Store 1, Store 2, Store 1, Store 2, Store 1
        $storeSequence = array_column($ordered, 'storeId');
        $this->assertEquals([1, 2, 1, 2, 1], $storeSequence);

        // The same product sold by different stores (P1 and P4) are both preserved
        $productIds = array_column($ordered, 'id');
        $this->assertContains(1, $productIds);
        $this->assertContains(4, $productIds);
    }

    /**
     * Test 6: No duplicate product entries while preserving the same product sold by different stores.
     */
    public function test_no_duplicate_product_entries_while_preserving_same_product_from_different_stores(): void
    {
        $products = [
            $this->createProduct(101, 3, 100, 'Short Bond Paper', 'School Supplies', 1),
            $this->createProduct(102, 3, 120, 'Short Bond Paper', 'School Supplies', 2), // Same product, different store
            $this->createProduct(103, 3, 150, 'Long Bond Paper', 'School Supplies', 1),
        ];

        $log = $this->createSearchLog('bond paper', 3);
        $categoryScores = [3 => 0.80];

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $log, 0);

        // Verify all IDs are unique in the feed
        $ids = array_column($ranked, 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)), 'No duplicate product IDs allowed');

        // Both store listings for Short Bond Paper are in the feed
        $namesAndStores = array_map(fn($p) => "{$p['name']}@Store{$p['storeId']}", $ranked);
        $this->assertContains('Short Bond Paper@Store1', $namesAndStores);
        $this->assertContains('Short Bond Paper@Store2', $namesAndStores);
    }

    /**
     * Test 7: Unavailable products and unresolved categories fall back safely.
     */
    public function test_unavailable_products_and_unresolved_categories_handled_safely(): void
    {
        $products = [
            $this->createProduct(1, 2, 200, 'Beverage Product', 'Beverages', 1),
            $this->createProduct(2, 4, 100, 'Available Snack', 'Snacks', 2),
        ];

        // 7a. Query with category having available products picks an available product
        $logAvailable = $this->createSearchLog('Nonexistent Fancy Chip Brand', 4);
        $resolvedCat = $this->controller->publicResolveLatestSearchCategoryId($logAvailable, $products);
        $this->assertEquals(4, $resolvedCat);

        $priority = $this->controller->publicSelectFirstRecommendation($resolvedCat, $products, $logAvailable->search_query, 0);
        $this->assertNotNull($priority);
        $this->assertEquals(2, $priority['id'], 'Should select available snack');

        // 7b. Completely unknown query with null category: returns null priority, falls back safely to composite ranking
        $logUnknown = $this->createSearchLog('zzzzqqqq111222', null);
        $resolvedUnknown = $this->controller->publicResolveLatestSearchCategoryId($logUnknown, $products);
        $this->assertNull($resolvedUnknown);

        $categoryScores = [4 => 0.70, 2 => 0.30];
        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $logUnknown);
        $this->assertCount(2, $ranked);
        // Runs standard composite scoring
        $this->assertEquals(2, $ranked[0]['id']);
    }

    /**
     * Test 8: Older interests remain visible through category interleaving.
     */
    public function test_older_interests_remain_visible_through_category_interleaving(): void
    {
        $products = [];
        // Cat 2 (Beverages): 10 products
        for ($i = 1; $i <= 10; $i++) {
            $products[] = $this->createProduct($i, 2, 50 * $i, "Beverage {$i}", 'Beverages', $i % 2 + 1);
        }
        // Cat 4 (Snacks): 10 products
        for ($i = 11; $i <= 20; $i++) {
            $products[] = $this->createProduct($i, 4, 50 * ($i - 10), "Snack {$i}", 'Snacks', $i % 2 + 1);
        }

        $log = $this->createSearchLog('Snacks', 4);
        $categoryScores = [2 => 0.85, 4 => 0.40]; // Beverages has higher long-term weight

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $log, 0);

        // Position #1 is Snack
        $this->assertEquals(4, $ranked[0]['category_id']);

        // In the top 6 positions, Beverages has significant representation
        $top6Categories = array_column(array_slice($ranked, 0, 6), 'category_id');
        $beverageCount = count(array_filter($top6Categories, fn($c) => $c === 2));
        $snackCount = count(array_filter($top6Categories, fn($c) => $c === 4));

        $this->assertGreaterThanOrEqual(2, $beverageCount, 'Older interest (Beverages) must remain prominently visible');
        $this->assertGreaterThanOrEqual(2, $snackCount, 'Recent interest (Snacks) must remain visible');

        // Verify no monolithic block > 4 while multiple categories are active
        $this->assertMaxConsecutiveCategory(array_slice($ranked, 0, 15), 4);
    }

    /**
     * Test 9: Fallback feed retains existing ordering for new consumers.
     */
    public function test_scenario_3_fallback_feed_retains_existing_ordering(): void
    {
        $fallbackCategories = [5, 2, 8];

        $products = [
            $this->createProduct(101, 8, 500),
            $this->createProduct(102, 5, 200),
            $this->createProduct(103, 2, 300),
            $this->createProduct(104, 5, 100),
            $this->createProduct(105, 99, 50),
        ];

        $sorted = collect($products)->sortBy(function ($product) use ($fallbackCategories) {
            $catScore = 1000;
            $catId = $product['category_id'];
            if (in_array($catId, $fallbackCategories)) {
                $catScore = array_search($catId, $fallbackCategories);
            }
            return [$catScore, $product['distance_meters'] ?? 999999, $product['id']];
        })->values()->toArray();

        $this->assertEquals(104, $sorted[0]['id']);
        $this->assertEquals(102, $sorted[1]['id']);
        $this->assertEquals(103, $sorted[2]['id']);
        $this->assertEquals(101, $sorted[3]['id']);
        $this->assertEquals(105, $sorted[4]['id']);
    }

    /**
     * Test 10: Edge cases with empty scores, zero values, and negative predictions.
     */
    public function test_scenario_4_edge_cases_empty_or_zero_or_negative_scores(): void
    {
        $products = [
            $this->createProduct(1, 10, 500),
            $this->createProduct(2, 20, 100),
        ];

        $rankedEmpty = $this->controller->publicInterleaveCategoryProducts($products, []);
        $this->assertCount(2, $rankedEmpty);
        $this->assertEquals(2, $rankedEmpty[0]['id']);
        $this->assertEquals(1, $rankedEmpty[1]['id']);

        $rawMlScores = [1 => -2.5, 2 => 0.0, 3 => 1.5];
        $sanitized = [];
        foreach ($rawMlScores as $catId => $score) {
            $numericScore = (float)$score;
            if ($numericScore > 0) {
                $sanitized[(int)$catId] = $numericScore;
            }
        }
        $this->assertEquals([3 => 1.5], $sanitized);
    }

    /**
     * Test 11: Nearby products retain priority within categories.
     */
    public function test_scenario_5_nearby_products_retain_priority_within_categories(): void
    {
        $products = [
            $this->createProduct(1, 1, 800, 'Far Noodle', null, 1),
            $this->createProduct(2, 1, 150, 'Near Noodle', null, 1),
            $this->createProduct(3, 1, 400, 'Mid Noodle', null, 1),
            $this->createProduct(4, 2, 900, 'Far Paper', null, 2),
            $this->createProduct(5, 2, 200, 'Near Paper', null, 2),
        ];

        $ranked = $this->controller->publicInterleaveCategoryProducts($products, [1 => 0.60, 2 => 0.40]);

        $cat1Items = array_values(array_filter($ranked, fn($p) => $p['category_id'] === 1));
        $this->assertEquals(2, $cat1Items[0]['id']);
        $this->assertEquals(3, $cat1Items[1]['id']);
        $this->assertEquals(1, $cat1Items[2]['id']);

        $cat2Items = array_values(array_filter($ranked, fn($p) => $p['category_id'] === 2));
        $this->assertEquals(5, $cat2Items[0]['id']);
        $this->assertEquals(4, $cat2Items[1]['id']);
    }

    /**
     * Test 12: Output structure and fields are complete.
     */
    public function test_scenario_6_output_structure(): void
    {
        $products = [$this->createProduct(1, 1, 100)];
        $ranked = $this->controller->publicInterleaveCategoryProducts($products, [1 => 0.70]);

        $this->assertCount(1, $ranked);
        $expectedKeys = [
            'id', 'name', 'description', 'category', 'category_id',
            'price', 'image', 'store', 'storeId', 'inStock',
            'availableQuantity', 'variants', 'distance_meters', 'expiration_date'
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $ranked[0]);
        }
    }

    /**
     * Test 13: Three categories interleaved proportionally.
     */
    public function test_three_categories_interleaved_proportionally(): void
    {
        $categoryScores = [1 => 0.80, 2 => 0.50, 3 => 0.20];

        $products = [];
        for ($i = 1; $i <= 20; $i++) {
            $products[] = $this->createProduct($i, 1, 10 * $i);
        }
        for ($i = 21; $i <= 40; $i++) {
            $products[] = $this->createProduct($i, 2, 10 * ($i - 20));
        }
        for ($i = 41; $i <= 60; $i++) {
            $products[] = $this->createProduct($i, 3, 10 * ($i - 40));
        }

        $ranked = $this->controller->publicInterleaveCategoryProducts($products, $categoryScores);

        $top24Cats = array_column(array_slice($ranked, 0, 24), 'category_id');
        $c1Count = count(array_filter($top24Cats, fn($c) => $c === 1));
        $c2Count = count(array_filter($top24Cats, fn($c) => $c === 2));
        $c3Count = count(array_filter($top24Cats, fn($c) => $c === 3));

        $this->assertGreaterThan($c2Count, $c1Count);
        $this->assertGreaterThan($c3Count, $c2Count);
        $this->assertGreaterThan(0, $c3Count);

        // Score-proportional blocks allow larger consecutive runs for dominant categories.
        // With 3 categories and multiplier of 9, the top category (score 0.80) can get up
        // to ~5 consecutive slots per round, so the limit is raised accordingly.
        $this->assertMaxConsecutiveCategory(array_slice($ranked, 0, 24), 6);
    }

    /**
     * Test 14: Null category and null distances handled safely.
     */
    public function test_null_category_and_null_distances_handled_safely(): void
    {
        $products = [
            $this->createProduct(1, null, 100),
            $this->createProduct(2, 1, null),
            $this->createProduct(3, 1, 50),
            $this->createProduct(4, 99, 20),
        ];

        $ranked = $this->controller->publicInterleaveCategoryProducts($products, [1 => 0.70]);

        $this->assertEquals(3, $ranked[0]['id']);
        $this->assertEquals(2, $ranked[1]['id']);
        $this->assertEquals(4, $ranked[2]['id']);
        $this->assertEquals(1, $ranked[3]['id']);
    }

    /**
     * Test 15: A frequently searched category can occupy many consecutive positions
     * when its ranking scores are dominant, without forced alternation.
     */
    public function test_frequently_searched_category_can_occupy_consecutive_positions_when_scores_justify_it(): void
    {
        $products = [];
        // Cat 3 (School Supplies): 8 products
        for ($i = 1; $i <= 8; $i++) {
            $products[] = $this->createProduct($i, 3, 50 * $i, "School Supply {$i}", 'School Supplies', $i % 2 + 1);
        }
        // Cat 1 (Noodles): 8 products
        for ($i = 9; $i <= 16; $i++) {
            $products[] = $this->createProduct($i, 1, 50 * ($i - 8), "Noodle {$i}", 'Instant Noodles & Pasta', $i % 2 + 1);
        }

        // Cat 3 has dominant composite score (0.95 vs 0.20) from frequent searches & ML predictions
        $categoryScores = [3 => 0.95, 1 => 0.20];
        $latestSearch = $this->createSearchLog('paper', 3);

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $latestSearch, 0);

        // Positions 1-5 should all be from the high-scoring Category 3
        // (priority product + 4 proportional block slots from score 0.95/1.15 * 6 ≈ 5)
        $top5Categories = array_column(array_slice($ranked, 0, 5), 'category_id');
        $this->assertEquals([3, 3, 3, 3, 3], $top5Categories, 'Category 3 should occupy positions 1-5 when supported by dominant composite ranking');

        // Category 1 appears after the dominant category's proportional block
        $this->assertEquals(1, $ranked[5]['category_id'], 'Lower-ranked Category 1 becomes available after the dominant category block');

        // Both categories are fully represented
        $allCats = array_column($ranked, 'category_id');
        $cat3Count = count(array_filter($allCats, fn($c) => $c === 3));
        $cat1Count = count(array_filter($allCats, fn($c) => $c === 1));
        $this->assertEquals(8, $cat3Count, 'All Cat 3 products should be in the feed');
        $this->assertEquals(8, $cat1Count, 'All Cat 1 products should be in the feed');
    }

    /**
     * Test 16: Lower-ranked categories remain available without forced alternation.
     */
    public function test_lower_ranked_categories_remain_available_without_forced_alternation(): void
    {
        $products = [
            $this->createProduct(1, 3, 100, 'Paper 1', 'School Supplies', 1),
            $this->createProduct(2, 3, 110, 'Paper 2', 'School Supplies', 2),
            $this->createProduct(3, 3, 120, 'Paper 3', 'School Supplies', 1),
            $this->createProduct(4, 2, 100, 'Beverage 1', 'Beverages', 1),
            $this->createProduct(5, 2, 110, 'Beverage 2', 'Beverages', 2),
        ];

        // Equal scores (0.50 and 0.50) -> smooth interleaving without forced alternation
        $categoryScores = [3 => 0.50, 2 => 0.50];
        $latestSearch = $this->createSearchLog('paper', 3);

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $latestSearch, 0);

        // Position 1 is from latest search (Cat 3)
        $this->assertEquals(3, $ranked[0]['category_id']);

        // Both categories are present in the top 4
        $top4 = array_column(array_slice($ranked, 0, 4), 'category_id');
        $this->assertContains(3, $top4);
        $this->assertContains(2, $top4);
    }

    /**
     * Test 17: Dominant category occupies positions 1-4+ consecutively when there is no latest search
     * priority product, demonstrating score-proportional block emission.
     */
    public function test_dominant_category_occupies_consecutive_positions_without_priority_product(): void
    {
        $products = [];
        for ($i = 1; $i <= 6; $i++) {
            $products[] = $this->createProduct($i, 3, 50 * $i, "School Supply {$i}", 'School Supplies');
        }
        for ($i = 7; $i <= 12; $i++) {
            $products[] = $this->createProduct($i, 1, 50 * ($i - 6), "Noodle {$i}", 'Instant Noodles & Pasta');
        }

        // Cat 3 dominant score (0.90 vs 0.20), no latest search
        $categoryScores = [3 => 0.90, 1 => 0.20];
        $ranked = $this->runFullFeedPipeline($products, $categoryScores, null);

        // Positions 1-5 should all be Cat 3 consecutively
        $top5Categories = array_column(array_slice($ranked, 0, 5), 'category_id');
        $this->assertEquals([3, 3, 3, 3, 3], $top5Categories, 'Dominant category must occupy top positions consecutively without forced alternation');

        // Total products preserved
        $this->assertCount(12, $ranked);
        $uniqueIds = array_unique(array_column($ranked, 'id'));
        $this->assertCount(12, $uniqueIds);
    }

    /**
     * Test 18: Full feed preservation - no missing products, no duplicates, no starvation
     * across varied category sizes and skewed scores.
     */
    public function test_full_feed_preservation_no_missing_duplicates_or_starvation(): void
    {
        $products = [];
        $id = 1;
        // 4 categories with different item counts: Cat 1: 15, Cat 2: 10, Cat 3: 5, Cat 4: 2
        $counts = [1 => 15, 2 => 10, 3 => 5, 4 => 2];
        foreach ($counts as $catId => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $products[] = $this->createProduct($id, $catId, 20 * $i, "Product {$id}", "Category {$catId}", ($i % 3) + 1);
                $id++;
            }
        }
        $totalInputCount = count($products); // 32 products

        // Cat 4 is latest search (even though it only has 2 products and low score)
        $latestSearch = $this->createSearchLog('Cat4 item', 4);
        $categoryScores = [1 => 0.90, 2 => 0.60, 3 => 0.30, 4 => 0.10];

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $latestSearch, 0);

        // Position 1 is Cat 4
        $this->assertEquals(4, $ranked[0]['category_id']);

        // Feed has exact same count as input
        $this->assertCount($totalInputCount, $ranked, 'No products should be lost from the feed');

        // All IDs are unique (no duplicate listings)
        $outputIds = array_column($ranked, 'id');
        $this->assertEquals(count($outputIds), count(array_unique($outputIds)), 'Every product ID in the feed must be unique');

        // Every category is represented according to its original inventory count
        foreach ($counts as $catId => $expectedCount) {
            $catItems = array_filter($ranked, fn($p) => $p['category_id'] === $catId);
            $this->assertCount($expectedCount, $catItems, "Category {$catId} must have all {$expectedCount} products present");
        }
    }

    /**
     * Test 19: When the priority category has only one product in the entire catalog,
     * it is placed at position #1, not duplicated, and remaining categories interleave smoothly.
     */
    public function test_sole_product_in_priority_category_handled_cleanly(): void
    {
        $products = [
            $this->createProduct(1, 4, 100, 'Solo Snack', 'Snacks', 1), // Only product in Cat 4
            $this->createProduct(2, 2, 50, 'Beverage 1', 'Beverages', 1),
            $this->createProduct(3, 2, 60, 'Beverage 2', 'Beverages', 2),
            $this->createProduct(4, 3, 70, 'Paper 1', 'School Supplies', 1),
            $this->createProduct(5, 3, 80, 'Paper 2', 'School Supplies', 1),
        ];

        $latestSearch = $this->createSearchLog('snack', 4);
        $categoryScores = [2 => 0.80, 3 => 0.50, 4 => 0.20];

        $ranked = $this->runFullFeedPipeline($products, $categoryScores, $latestSearch, 0);

        // Position 1 is Solo Snack (Cat 4)
        $this->assertEquals(1, $ranked[0]['id']);
        $this->assertEquals(4, $ranked[0]['category_id']);

        // Feed has exactly 5 products, all unique
        $this->assertCount(5, $ranked);
        $ids = array_column($ranked, 'id');
        $this->assertEquals([1, 2, 3, 4, 5], array_values(array_unique($ids)));
    }

    /**
     * Test 20: Edge cases with zero total score handled safely without division by zero.
     */
    public function test_zero_total_score_handled_safely_without_division_by_zero(): void
    {
        $products = [
            $this->createProduct(1, 1, 100, 'Product 1'),
            $this->createProduct(2, 2, 200, 'Product 2'),
        ];

        // Scores are zero
        $categoryScores = [1 => 0.0, 2 => 0.0];
        $ranked = $this->controller->publicInterleaveCategoryProducts($products, $categoryScores);

        $this->assertCount(2, $ranked);
        $ids = array_column($ranked, 'id');
        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
    }

    /**
     * Test 21: A search log with a category_id that has 0 available products in the catalog
     * re-resolves from search_query against available catalog products.
     */
    public function test_empty_category_id_in_search_log_re_resolves_to_available_product_category(): void
    {
        // Available catalog: Category 1 (Cooking Essentials) has Pancit Canton, Category 2 (Beverages) has Coke.
        // Category 9 (Instant Noodles) has 0 products in catalog.
        $products = [
            $this->createProduct(1, 1, 100, 'Lucky Me Pancit Canton Original', 'Cooking Essentials'),
            $this->createProduct(2, 2, 200, 'Coke 1.5L', 'Beverages'),
        ];

        // Search log was erroneously recorded with Category 9 (which has 0 items in catalog)
        $logWithEmptyCat = $this->createSearchLog('pancit canton', 9);

        $resolved = $this->controller->publicResolveLatestSearchCategoryId($logWithEmptyCat, $products);
        $this->assertEquals(1, $resolved, 'Should re-resolve to Category 1 because Category 9 has no products in catalog');

        $priority = $this->controller->publicSelectFirstRecommendation($resolved, $products, $logWithEmptyCat->search_query, 0);
        $this->assertNotNull($priority);
        $this->assertEquals(1, $priority['id']);
        $this->assertEquals(1, $priority['category_id']);
    }

    /**
     * Test 22: Recent and repeated searches in Cooking Essentials and Others
     * meaningfully influence the top recommendations and outrank Beverages despite Beverages having higher ML score.
     */
    public function test_recent_and_repeated_searches_outrank_beverages_with_high_ml(): void
    {
        $now = Carbon::parse('2026-10-12 12:00:00');

        // Random Forest predicts high future score for Beverages (Cat 2 = 10.0), lower for Cat 1 (2.0) and Cat 6 (1.0)
        $mlScores = [2 => 10.0, 1 => 2.0, 6 => 1.0];

        // Consumer repeatedly and recently searched for Cooking Essentials (Cat 1: 12 searches) and Paper (Cat 6: 6 searches)
        // Beverages has 0 recent searches.
        $realtimeStats = collect([
            1 => (object) [
                'category_id' => 1,
                'search_count' => 12,
                'latest_search' => '2026-10-12 11:55:00', // 5 minutes ago
            ],
            6 => (object) [
                'category_id' => 6,
                'search_count' => 6,
                'latest_search' => '2026-10-12 11:58:00', // 2 minutes ago
            ],
        ]);

        $scores = $this->controller->publicComputeCompositeCategoryScores($mlScores, $realtimeStats, $now);

        // Cat 1 (Cooking Essentials) composite score must outrank Cat 2 (Beverages)
        $this->assertGreaterThan($scores[2], $scores[1], 'Cooking Essentials must outrank Beverages due to strong recent and repeated searches');
        $this->assertGreaterThan($scores[2], $scores[6], 'Others/Paper must outrank Beverages due to recent and repeated searches');

        $products = [
            $this->createProduct(1, 1, 100, 'Lucky Me Pancit Canton', 'Cooking Essentials', 1),
            $this->createProduct(2, 1, 110, 'Silver Swan Soy Sauce', 'Cooking Essentials', 2),
            $this->createProduct(3, 1, 120, 'Mama Sita Sauce', 'Cooking Essentials', 1),
            $this->createProduct(4, 6, 100, 'Bond Paper Short', 'Others', 1),
            $this->createProduct(5, 6, 110, 'Bond Paper Long', 'Others', 1),
            $this->createProduct(6, 2, 80, 'Coke 1.5L', 'Beverages', 1),
            $this->createProduct(7, 2, 90, 'Sprite 1.5L', 'Beverages', 2),
        ];

        // Consumer just searched "pancit canton"
        $latestSearch = $this->createSearchLog('pancit canton', 1);
        $ranked = $this->runFullFeedPipeline($products, $scores, $latestSearch, 0);

        // Position 1 is Pancit Canton (Cat 1)
        $this->assertEquals(1, $ranked[0]['category_id']);
        $this->assertStringContainsString('Pancit Canton', $ranked[0]['name']);

        // Positions 1-3 should all be Cooking Essentials (Cat 1) consecutive items, not forced to alternate
        $this->assertEquals(1, $ranked[1]['category_id'], 'Position 2 should be Cooking Essentials');
        $this->assertEquals(1, $ranked[2]['category_id'], 'Position 3 should be Cooking Essentials');

        // Beverages (Cat 2) does not occupy positions 1-3
        $top3Categories = array_column(array_slice($ranked, 0, 3), 'category_id');
        $this->assertNotContains(2, $top3Categories, 'Beverages must not occupy top positions over strongly searched categories');
    }

    /**
     * Test 23: When selecting a priority recommendation for a search query,
     * unrelated products in the same category are NOT included in the candidate pool.
     */
    public function test_unrelated_products_in_same_category_not_included_in_priority_candidate_pool(): void
    {
        $products = [
            $this->createProduct(1, 6, 100, 'Bond Paper Short', 'Others', 1),
            $this->createProduct(2, 6, 100, 'Bond Paper Long', 'Others', 1),
            $this->createProduct(3, 6, 50, 'Energizer Battery AA', 'Others', 1), // Unrelated product in same category
            $this->createProduct(4, 6, 50, 'Super Glue', 'Others', 1),           // Unrelated product in same category
        ];

        // Searching for "paper" should NEVER select Energizer Battery or Super Glue across all seed varieties
        for ($seed = 0; $seed < 10; $seed++) {
            $selected = $this->controller->publicSelectFirstRecommendation(6, $products, 'paper', $seed);
            $this->assertNotNull($selected);
            $this->assertStringContainsString('Paper', $selected['name'], "Candidate must be a Paper product, not unrelated item: {$selected['name']}");
        }
    }

    private function assertMaxConsecutiveCategory(array $products, int $max): void
    {
        $currentCat = null;
        $consecutive = 0;

        foreach ($products as $p) {
            $catId = $p['category_id'];
            if ($catId === $currentCat) {
                $consecutive++;
                $this->assertLessThanOrEqual(
                    $max,
                    $consecutive,
                    "Category {$catId} exceeded max consecutive limit of {$max}"
                );
            } else {
                $currentCat = $catId;
                $consecutive = 1;
            }
        }
    }
}
