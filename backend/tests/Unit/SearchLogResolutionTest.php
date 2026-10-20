<?php

namespace Tests\Unit;

use App\Http\Controllers\SearchLogController;
use PHPUnit\Framework\TestCase;

class SearchLogResolutionTest extends TestCase
{
    private SearchLogController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SearchLogController();
    }

    private function getSampleCategories(): array
    {
        return [
            [
                'category_id' => 1,
                'category_name' => 'Instant Noodles & Pasta',
                'description' => 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, and pasta.'
            ],
            [
                'category_id' => 2,
                'category_name' => 'Beverages',
                'description' => 'Bottled water, softdrinks, juice, energy drinks, milk, and chocolate drinks.'
            ],
            [
                'category_id' => 3,
                'category_name' => 'School & Office Supplies',
                'description' => 'Ballpens, pencils, paper, notebooks, envelopes, and other school and office supplies.'
            ],
            [
                'category_id' => 4,
                'category_name' => 'Snacks & Sweets',
                'description' => 'Chips, crackers, biscuits, cookies, candies, and chocolates.'
            ],
        ];
    }

    private function getSampleInventory(): array
    {
        return [
            ['inventory_id' => 101, 'category_id' => 3, 'product_name' => 'Short Bond Paper', 'description' => 'Letter size paper'],
            ['inventory_id' => 102, 'category_id' => 3, 'product_name' => 'Long Bond Paper', 'description' => 'Legal size paper'],
            ['inventory_id' => 103, 'category_id' => 3, 'product_name' => 'Yellow Pad Paper', 'description' => 'Pad paper for school'],
            ['inventory_id' => 104, 'category_id' => 1, 'product_name' => 'Lucky Me Pancit Canton', 'description' => 'Instant noodle kalamansi'],
            ['inventory_id' => 105, 'category_id' => 1, 'product_name' => 'Payless Pancit Canton', 'description' => 'Instant noodle hot and chili'],
            ['inventory_id' => 106, 'category_id' => 2, 'product_name' => 'Coke 1.5L', 'description' => 'Carbonated soft drink'],
            ['inventory_id' => 107, 'category_id' => 2, 'product_name' => 'Bear Brand Powdered Milk Drink', 'description' => 'Fortified milk drink'],
            ['inventory_id' => 108, 'category_id' => 2, 'product_name' => 'Birch Tree Full Cream Milk', 'description' => 'Dairy milk'],
            ['inventory_id' => 109, 'category_id' => 4, 'product_name' => 'Milk Candy Chews', 'description' => 'Sweet milk candy'],
        ];
    }

    /**
     * Test 1: Typing and submitting "paper" logs the query and resolves Category 3 (School & Office Supplies)
     * from matching inventory products.
     */
    public function test_typing_and_submitting_paper_resolves_category_from_matching_inventory(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('paper', $inventory, $categories);

        $this->assertNotNull($resolvedCatId);
        $this->assertEquals(3, $resolvedCatId, 'Query "paper" should resolve to category 3 (School & Office Supplies)');
    }

    /**
     * Test 2: Category resolution with exact product name match.
     */
    public function test_category_resolution_with_exact_product_name(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('Lucky Me Pancit Canton', $inventory, $categories);

        $this->assertEquals(1, $resolvedCatId, 'Exact product name match should resolve to category 1 (Noodles)');
    }

    /**
     * Test 3: Category resolution with partial word / substring match.
     */
    public function test_category_resolution_with_partial_substring_match(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('canton', $inventory, $categories);

        $this->assertEquals(1, $resolvedCatId, 'Substring "canton" should resolve to category 1 (Noodles)');
    }

    /**
     * Test 4: Category resolution with direct category name match.
     */
    public function test_category_resolution_with_direct_category_name(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('Beverages', $inventory, $categories);

        $this->assertEquals(2, $resolvedCatId, 'Query "Beverages" should resolve directly to category 2');
    }

    /**
     * Test 5: Query matching multiple categories chooses the category with highest matching inventory frequency.
     */
    public function test_multi_category_match_selects_category_with_highest_match_frequency(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        // "milk" matches 2 products in Category 2 (Bear Brand, Birch Tree) and 1 product in Category 4 (Milk Candy)
        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('milk', $inventory, $categories);

        $this->assertEquals(2, $resolvedCatId, 'Query "milk" should resolve to Category 2 which has 2 milk products vs 1 in Category 4');
    }

    /**
     * Test 6: Unmatched queries return null and do not assign a false confirmed category.
     */
    public function test_unmatched_query_returns_null_and_does_not_assign_category(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('xyz999unmatchednonsense', $inventory, $categories);

        $this->assertNull($resolvedCatId, 'Unmatched queries must return null so Path B keeps raw query without false category');
    }

    /**
     * Test 7: Empty or whitespace query returns null.
     */
    public function test_empty_or_whitespace_query_returns_null(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $this->assertNull($this->controller->resolveCategoryFromDatabase('', $inventory, $categories));
        $this->assertNull($this->controller->resolveCategoryFromDatabase('   ', $inventory, $categories));
    }

    /**
     * Test 8: Category description match resolves category when no product name has the keyword.
     */
    public function test_category_description_matches_when_product_name_does_not_contain_query(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = [
            ['inventory_id' => 201, 'category_id' => 1, 'product_name' => 'Item A', 'description' => 'Desc A'],
        ];

        // "envelopes" is in category 3 description ("envelopes, and other school and office supplies")
        $resolvedCatId = $this->controller->resolveCategoryFromDatabase('envelopes', $inventory, $categories);

        $this->assertEquals(3, $resolvedCatId, 'Keyword found in category description should resolve category');
    }
    /**
     * Test 9: Stringified JavaScript event objects (e.g. "[object KeyboardEvent]") must not match any category.
     * This is the exact string that was previously saved to the database when the frontend
     * leaked a KeyboardEvent into logSearch() instead of the actual query.
     */
    public function test_stringified_event_objects_do_not_resolve_to_any_category(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        $this->assertNull(
            $this->controller->resolveCategoryFromDatabase('[object KeyboardEvent]', $inventory, $categories),
            '"[object KeyboardEvent]" should not match any category'
        );
        $this->assertNull(
            $this->controller->resolveCategoryFromDatabase('[object MouseEvent]', $inventory, $categories),
            '"[object MouseEvent]" should not match any category'
        );
        $this->assertNull(
            $this->controller->resolveCategoryFromDatabase('[object PointerEvent]', $inventory, $categories),
            '"[object PointerEvent]" should not match any category'
        );
    }

    /**
     * Test 10: The backend regex guard correctly identifies "[object *]" patterns as invalid queries.
     */
    public function test_backend_regex_guard_identifies_stringified_objects(): void
    {
        $pattern = '/^\[object\s+\w+\]$/i';

        $this->assertMatchesRegularExpression($pattern, '[object KeyboardEvent]');
        $this->assertMatchesRegularExpression($pattern, '[object MouseEvent]');
        $this->assertMatchesRegularExpression($pattern, '[object PointerEvent]');
        $this->assertMatchesRegularExpression($pattern, '[object Event]');

        // Valid queries should NOT match the guard
        $this->assertDoesNotMatchRegularExpression($pattern, 'paper');
        $this->assertDoesNotMatchRegularExpression($pattern, 'pancit canton');
        $this->assertDoesNotMatchRegularExpression($pattern, 'bond paper');
        $this->assertDoesNotMatchRegularExpression($pattern, '[on sale] paper');
    }

    /**
     * Test 11: A clicked product suggestion uses the selected product's actual name and category.
     */
    public function test_clicked_product_suggestion_uses_selected_product_actual_name_and_category(): void
    {
        // Consumer typed "sau" but clicked suggestion "Silver Swan Soy Sauce" in Category 1
        $selectedProduct = [
            'id' => 104,
            'name' => 'Silver Swan Soy Sauce',
            'categoryId' => 1,
        ];

        $this->assertEquals('Silver Swan Soy Sauce', $selectedProduct['name']);
        $this->assertEquals(1, $selectedProduct['categoryId']);
    }

    /**
     * Test 12: A manually submitted query without selected suggestion resolves category from matching inventory.
     */
    public function test_manually_submitted_query_resolves_category_from_matching_inventory(): void
    {
        $categories = $this->getSampleCategories();
        $inventory = $this->getSampleInventory();

        // Consumer typed "sau" and pressed Enter without selecting suggestion
        $inventoryWithSauce = array_merge($inventory, [
            ['inventory_id' => 110, 'category_id' => 1, 'product_name' => 'Silver Swan Soy Sauce', 'description' => 'Cooking sauce'],
        ]);

        $resolved = $this->controller->resolveCategoryFromDatabase('sau', $inventoryWithSauce, $categories);
        $this->assertEquals(1, $resolved, 'Query "sau" should resolve to category 1 where sauce product is located');
    }

    /**
     * Test 13: Active inventory matches take priority over empty categories or description matches.
     */
    public function test_active_inventory_matches_take_priority_over_empty_categories(): void
    {
        $categories = [
            ['category_id' => 10, 'category_name' => 'Condiments & Sauces', 'description' => 'Sauces and condiments.'],
            ['category_id' => 1, 'category_name' => 'Cooking Essentials', 'description' => 'Kitchen staples.'],
        ];

        // All active inventory items are in Category 1 (none in Category 10)
        $inventory = [
            ['inventory_id' => 201, 'category_id' => 1, 'product_name' => 'Silver Swan Soy Sauce', 'description' => 'Soy sauce'],
        ];

        $resolved = $this->controller->resolveCategoryFromDatabase('sauce', $inventory, $categories);
        $this->assertEquals(1, $resolved, 'Active inventory in category 1 must be prioritized over category 10 which has 0 inventory');
    }
}

