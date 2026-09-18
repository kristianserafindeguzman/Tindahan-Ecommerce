<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand the category list, rewrite the category descriptions, and file the seeded catalog
     * under the new specific categories.
     *
     * Corrective follow-up to 2026_09_18_000001_rewrite_category_descriptions, which has already
     * run and is therefore left untouched.
     *
     * Safety properties:
     *  - No category is deleted and no category_id changes, so every inventory row keeps pointing
     *    at a valid category.
     *  - Products are matched by exact product_name and only where product_picture starts with
     *    'seed-images/'. Vendor uploads are stored under 'products/', so a vendor's own product
     *    can never be matched, now or later.
     *  - Descriptions are only rewritten while they still hold a text this project seeded, so a
     *    description a vendor edited on the Categories page is left exactly as they wrote it.
     *  - Running it twice changes nothing the second time.
     */

    /** Products this project seeds carry this picture prefix; vendor uploads never do. */
    private const SEEDED_PICTURE_PREFIX = 'seed-images/%';

    /** Every category, its final description, and the seeded texts it may still be holding. */
    private function categories(): array
    {
        return [
            [
                'name' => 'Cooking Essentials',
                'description' => 'Cooking oil, sugar, salt, pepper, spices, seasonings, garlic, onion, and basic cooking ingredients.',
                'seeded' => [
                    'Cooking Oil, Condiments & Sauces, Canned Goods, Noodles, Spices & Seasonings, Rice & Grains.',
                    'Ingredients for cooking a meal: rice, cooking oil, salt, pepper and other seasonings, soy sauce, vinegar, fish sauce and other condiments, canned goods, and instant noodles.',
                    'Cooking oil, sugar, salt, spices, seasonings, and other basic cooking ingredients.',
                ],
            ],
            [
                'name' => 'Beverages',
                'description' => 'Bottled water, softdrinks, juice, energy drinks, sports drinks, and alcoholic drinks.',
                'seeded' => [
                    'Softdrinks & Water, Coffee & Tea, Alcoholic Drinks, Powdered Juices.',
                    'Anything you drink: bottled and mineral water, softdrinks, powdered and bottled juice, coffee, tea, powdered and canned milk, and alcoholic drinks like beer and gin.',
                    'Bottled water, softdrinks, juice, energy drinks, and alcoholic drinks.',
                ],
            ],
            [
                'name' => 'Snacks & Sweets',
                'description' => 'Chips, crackers, biscuits, cookies, wafers, nuts, and chicharon.',
                'seeded' => [
                    'Chips & Crackers, Biscuits & Cookies, Candies & Chocolates, Bread & Pastries.',
                    'Food eaten between meals: chips and crackers, biscuits and cookies, candies and chocolates, peanuts, and bread and pastries.',
                    'Chips, crackers, biscuits, cookies, nuts, and other ready-to-eat snacks.',
                ],
            ],
            [
                'name' => 'Personal Care',
                'description' => 'Bath soap, body wash, shampoo, conditioner, deodorant, razors, sanitary napkins, and cotton buds.',
                'seeded' => [
                    'Bath Soap & Body Wash, Shampoo & Conditioner, Oral Care (Toothpaste/Brushes), Feminine Hygiene.',
                    'Things you use on your body: bath soap and body wash, shampoo and conditioner, toothpaste and toothbrushes, deodorant, razors, and napkins and other feminine hygiene needs.',
                    'Bath soap, body wash, shampoo, conditioner, deodorant, razors, and other personal hygiene products.',
                ],
            ],
            [
                'name' => 'Laundry & Cleaning',
                'description' => 'Laundry detergent, fabric conditioner, bleach, dishwashing liquid, and toilet and floor cleaners.',
                'seeded' => [
                    'Laundry Detergents, Fabric Conditioners, Dishwashing, Household Cleaners.',
                    'For washing clothes and cleaning the house: powder and bar laundry detergent, fabric conditioner, dishwashing liquid and sponges, bleach, and floor, toilet and glass cleaners.',
                    'Laundry detergent, fabric conditioner, bleach, dishwashing liquid, sponges, and household cleaning products.',
                ],
            ],
            [
                'name' => 'Others',
                'description' => 'Miscellaneous items not covered by the other categories.',
                'seeded' => [
                    'OTC Medicines & First Aid, School & Office Supplies, Miscellaneous.',
                    'Anything that does not fit the other categories: over-the-counter medicine such as paracetamol, first-aid supplies like bandages and alcohol, school and office supplies such as pens, paper and notebooks, plus batteries, candles and load cards.',
                    'Products that do not fit any of the available categories.',
                ],
            ],
            [
                'name' => 'Rice & Grains',
                'description' => 'Rice, corn, oats, and other grains sold per kilo or per pack.',
                'seeded' => [
                    'Rice, corn, oatmeal, and other grains sold by the kilo or in packs.',
                ],
            ],
            [
                'name' => 'Canned & Packaged Foods',
                'description' => 'Sardines, canned tuna, corned beef, luncheon meat, and other canned goods.',
                'seeded' => [
                    'Sardines, canned tuna, corned beef, luncheon meat, and other canned or packaged food.',
                ],
            ],
            [
                'name' => 'Instant Noodles & Pasta',
                'description' => 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, and pasta.',
                'seeded' => [
                    'Instant noodles, cup noodles, pancit canton, bihon, and pasta.',
                ],
            ],
            [
                'name' => 'Condiments & Sauces',
                'description' => 'Soy sauce, vinegar, fish sauce, ketchup, mayonnaise, seasoning mixes, and spreads.',
                'seeded' => [
                    'Soy sauce, vinegar, fish sauce, ketchup, mayonnaise, seasoning mixes, and spreads.',
                ],
            ],
            [
                'name' => 'Chilled & Processed Foods',
                'description' => 'Hotdog, longganisa, tocino, cheese, margarine, and eggs.',
                'seeded' => [
                    'Hotdog, longganisa, tocino, cheese, margarine, eggs, and other chilled or processed food.',
                ],
            ],
            [
                'name' => 'Coffee, Milk & Breakfast Drinks',
                'description' => 'Coffee, 3-in-1 coffee sachets, powdered and condensed milk, and chocolate drinks.',
                'seeded' => [
                    'Coffee, 3-in-1 sachets, powdered milk, chocolate drinks, and other breakfast drinks.',
                ],
            ],
            [
                'name' => 'Bread & Bakery',
                'description' => 'Pandesal, sliced bread, loaf bread, buns, and other bakery items.',
                'seeded' => [
                    'Pandesal, sliced bread, buns, and other bakery products.',
                ],
            ],
            [
                'name' => 'Candies & Chocolates',
                'description' => 'Candies, chocolates, mints, and other sweets.',
                'seeded' => [
                    'Candy, chocolate, mints, and other sweets sold per piece or per pack.',
                ],
            ],
            [
                'name' => 'Oral Care',
                'description' => 'Toothpaste, toothbrushes, mouthwash, and dental floss.',
                'seeded' => [
                    'Toothpaste, toothbrushes, mouthwash, and other dental care products.',
                ],
            ],
            [
                'name' => 'Baby Care',
                'description' => 'Diapers, baby soap, baby powder, baby wipes, and baby milk.',
                'seeded' => [
                    'Diapers, baby soap, baby powder, wipes, and other baby products.',
                ],
            ],
            [
                'name' => 'OTC Medicine & First Aid',
                'description' => 'Over-the-counter medicines, pain relievers, cold and flu tablets, antacids, bandages, antiseptics, alcohol, and face masks.',
                'seeded' => [
                    'Over-the-counter medicine, first-aid supplies, and basic health products.',
                ],
            ],
            [
                'name' => 'School & Office Supplies',
                'description' => 'Ballpens, pencils, paper, notebooks, envelopes, and other school and office supplies.',
                'seeded' => [
                    'Ballpen, pencil, paper, notebooks, envelopes, and other school or office supplies.',
                ],
            ],
            [
                'name' => 'Household Supplies',
                'description' => 'Tissue, trash bags, plastic bags, insect spray, brooms, and other household needs.',
                'seeded' => [
                    'Tissue, trash bags, plastic bags, insect spray, and other everyday household items.',
                ],
            ],
            [
                'name' => 'Batteries, Lighting & Electrical',
                'description' => 'Batteries, light bulbs, candles, matches, extension cords, and small electrical supplies.',
                'seeded' => [
                    'Batteries, light bulbs, candles, matches, and small electrical supplies.',
                ],
            ],
            [
                'name' => 'Mobile Load & E-Services',
                'description' => 'Prepaid load, e-load, mobile data promos, and e-wallet cash-in services.',
                'seeded' => [
                    'Prepaid load, e-load services, chargers, earphones, and other mobile accessories.',
                ],
            ],
            [
                'name' => 'Cigarettes & Tobacco',
                'description' => 'Cigarettes sold per stick or per pack, and other tobacco products.',
                'seeded' => [
                    'Cigarettes and other tobacco products sold per stick or per pack.',
                ],
            ],
        ];
    }

    /** Categories this migration adds when they are not present yet. */
    private function additions(): array
    {
        return [
            'Rice & Grains',
            'Canned & Packaged Foods',
            'Instant Noodles & Pasta',
            'Condiments & Sauces',
            'Chilled & Processed Foods',
            'Coffee, Milk & Breakfast Drinks',
            'Bread & Bakery',
            'Candies & Chocolates',
            'Oral Care',
            'Baby Care',
            'OTC Medicine & First Aid',
            'School & Office Supplies',
            'Household Supplies',
            'Batteries, Lighting & Electrical',
            'Mobile Load & E-Services',
            'Cigarettes & Tobacco',
        ];
    }

    /** Category names this project has renamed, old => new. */
    private function renames(): array
    {
        return [
            'Mobile Load & Accessories' => 'Mobile Load & E-Services',
        ];
    }

    /** Seeded products to file under a more specific category. */
    private function moves(): array
    {
        return [
            [
                'to' => 'Rice & Grains',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Jasmine Rice (per kilo)',
                    'Rice 1kg',
                    'Sinandomeng Rice (per kilo)',
                    'White Rice (per kilo)',
                ],
            ],
            [
                'to' => 'Canned & Packaged Foods',
                'from' => 'Cooking Essentials',
                'products' => [
                    '555 Sardines in Tomato Sauce 155g',
                    'Argentina Corned Beef 150g',
                    'CDO Corned Beef 150g',
                    'Century Tuna Flakes in Oil 155g',
                    'Corned Beef',
                    'Ligo Sardines 155g',
                    'Mega Sardines 155g',
                    'Purefoods Corned Beef 150g',
                    'San Marino Corned Tuna 150g',
                    'Spam Luncheon Meat 200g',
                    'Young\'s Town Sardines 155g',
                ],
            ],
            [
                'to' => 'Instant Noodles & Pasta',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Instant Pancit Bihon (pack)',
                    'Lucky Me Batchoy Extra Hot',
                    'Lucky Me Beef na Beef',
                    'Lucky Me La Paz Batchoy',
                    'Lucky Me Pancit Canton',
                    'Lucky Me Pancit Canton Original',
                    'Lucky Me Sotanghon Guisado',
                    'Nissin Cup Noodles Seafood',
                    'Nissin Yakisoba',
                    'Payless Beef Mami',
                    'Payless Instant Mami',
                    'Quickchow Instant Noodles',
                ],
            ],
            [
                'to' => 'Condiments & Sauces',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Ajinomoto 8g',
                    'Cheez Whiz 220g',
                    'Datu Puti Suka 350ml',
                    'Datu Puti Toyo 350ml',
                    'Knorr Liquid Seasoning 8ml',
                    'Knorr Sinigang Mix 44g',
                    'Lady\'s Choice Mayonnaise 220ml',
                    'Maggi Magic Sarap 8g',
                    'Magic Sarap 8g',
                    'Marca Piña Suka 350ml',
                    'Papa Banana Ketchup 320g',
                    'Peanut Butter 220g',
                    'Silver Swan Toyo 350ml',
                    'Soy Sauce',
                    'UFC Banana Ketchup 320g',
                    'Vinegar',
                ],
            ],
            [
                'to' => 'Chilled & Processed Foods',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Eden Cheese 165g',
                    'Egg (per piece)',
                    'Purefoods Tender Juicy Hotdog',
                    'Star Margarine 25g',
                    'Star Margarine Tub 250g',
                    'Swift Hotdog 1/4 (piece)',
                ],
            ],
            [
                'to' => 'Coffee, Milk & Breakfast Drinks',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Alaska Condensada 300ml',
                    'Alaska Evaporada 370ml',
                    'Blend 45 Coffee (sachet)',
                    'Great Taste White Coffee (sachet)',
                    'Kopiko Black 3-in-1 (sachet)',
                    'Nescafe 3-in-1 Original (sachet)',
                    'Nescafe Classic 3-in-1 Rich',
                    'San Mig Coffee 3-in-1 (sachet)',
                ],
            ],
            [
                'to' => 'Beverages',
                'from' => 'Cooking Essentials',
                'products' => [
                    'Emperador Light 375ml',
                    'Red Horse Beer (bote)',
                    'San Miguel Pale Pilsen (bote)',
                    'Tanduay Rhum 375ml',
                ],
            ],
            [
                'to' => 'Coffee, Milk & Breakfast Drinks',
                'from' => 'Beverages',
                'products' => [
                    'Bear Brand Powdered Milk',
                    'Milo Sachet 22g',
                    'Nescafe Classic',
                ],
            ],
            [
                'to' => 'Bread & Bakery',
                'from' => 'Snacks & Sweets',
                'products' => [
                    'Gardenia White Bread',
                    'Pandesal (per piece)',
                    'Tasty Bread (loaf)',
                ],
            ],
            [
                'to' => 'Candies & Chocolates',
                'from' => 'Snacks & Sweets',
                'products' => [
                    'Choc Nut',
                    'Chocnut Mini (piece)',
                    'Cloud 9 Chocolate',
                    'Cloud 9 Chocolate Bar',
                    'Mentos Mint Roll',
                    'Ricoa Curly Tops',
                    'Storck Chocolate Candy',
                ],
            ],
            [
                'to' => 'Oral Care',
                'from' => 'Personal Care',
                'products' => [
                    'Close Up Toothpaste 20g',
                    'Colgate Toothbrush',
                    'Colgate Toothpaste',
                    'Colgate Toothpaste 20g',
                    'Oral-B Toothbrush',
                ],
            ],
            [
                'to' => 'Baby Care',
                'from' => 'Personal Care',
                'products' => [
                    'Pampers Diaper (piece)',
                ],
            ],
            [
                'to' => 'OTC Medicine & First Aid',
                'from' => 'Personal Care',
                'products' => [
                    'Alcohol 70% 60ml',
                    'Band-Aid (piece)',
                ],
            ],
            [
                'to' => 'Laundry & Cleaning',
                'from' => 'Others',
                'products' => [
                    'Ariel Powder Detergent 55g',
                    'Champion Detergent Bar',
                    'Domex Toilet Cleaner 50ml',
                    'Downy Fabric Conditioner 20ml',
                    'Joy Dishwashing Liquid 20ml',
                    'Surf Powder Detergent 55g',
                    'Tide Powder Detergent 55g',
                    'Zonrox Bleach 250ml',
                ],
            ],
            [
                'to' => 'OTC Medicine & First Aid',
                'from' => 'Others',
                'products' => [
                    'Bioflu Tablet (piece)',
                    'Biogesic Tablet (piece)',
                    'Face Mask 50pcs',
                    'Green Cross Alcohol',
                    'Kremil-S Tablet (piece)',
                    'Mefenamic Acid 500mg (piece)',
                    'Neozep Forte (piece)',
                    'Paracetamol 500mg (piece)',
                ],
            ],
            [
                'to' => 'School & Office Supplies',
                'from' => 'Others',
                'products' => [
                    'Hard Copy Bond Paper A4',
                    'Panda Ballpen Black',
                ],
            ],
            [
                'to' => 'Household Supplies',
                'from' => 'Others',
                'products' => [
                    'Baygon Insect Spray (small)',
                    'Plastic Bag (small, piece)',
                    'Tissue Paper (roll)',
                    'Trash Bag (roll, small)',
                ],
            ],
            [
                'to' => 'Batteries, Lighting & Electrical',
                'from' => 'Others',
                'products' => [
                    'Candles (piece)',
                    'Energizer AA Batteries',
                    'Match/Posporo (box)',
                ],
            ],
            [
                'to' => 'Mobile Load & E-Services',
                'from' => 'Others',
                'products' => [
                    'DITO Load 30',
                    'GCash Cash-In Fee',
                    'Globe Load 15',
                    'Smart Load 30',
                    'TM Load 20',
                ],
            ],
            [
                'to' => 'Cigarettes & Tobacco',
                'from' => 'Others',
                'products' => [
                    'Fortune Menthol (stick)',
                    'Marlboro Red (stick)',
                    'Mighty Menthol (stick)',
                    'Philip Morris (stick)',
                    'Winston Red (stick)',
                ],
            ],
        ];
    }

    private function categoryId(string $name): ?int
    {
        return DB::table('categories')->where('category_name', $name)->value('category_id');
    }

    public function up(): void
    {
        // A database seeded from an in-between version may still carry the old name.
        foreach ($this->renames() as $old => $new) {
            if ($this->categoryId($old) !== null && $this->categoryId($new) === null) {
                DB::table('categories')->where('category_name', $old)->update(['category_name' => $new]);
            }
        }

        // category_name has no unique index, so existing names are skipped by hand rather than
        // relying on the database to reject them. This is what makes a second run a no-op.
        $existing = DB::table('categories')->pluck('category_name')->all();

        $insert = [];
        foreach ($this->additions() as $name) {
            if (!in_array($name, $existing, true)) {
                $insert[] = [
                    'category_name' => $name,
                    'description' => $this->descriptionFor($name),
                ];
            }
        }

        if ($insert) {
            DB::table('categories')->insert($insert);
        }

        foreach ($this->categories() as $category) {
            DB::table('categories')
                ->where('category_name', $category['name'])
                ->whereIn('description', $category['seeded'])
                ->update(['description' => $category['description']]);
        }

        foreach ($this->moves() as $move) {
            $targetId = $this->categoryId($move['to']);

            if ($targetId === null) {
                continue;
            }

            DB::table('inventory')
                ->whereIn('product_name', $move['products'])
                ->where('product_picture', 'like', self::SEEDED_PICTURE_PREFIX)
                ->update(['category_id' => $targetId]);
        }
    }

    public function down(): void
    {
        // Send the seeded products back to the category they came from.
        foreach ($this->moves() as $move) {
            $sourceId = $this->categoryId($move['from']);

            if ($sourceId === null) {
                continue;
            }

            DB::table('inventory')
                ->whereIn('product_name', $move['products'])
                ->where('product_picture', 'like', self::SEEDED_PICTURE_PREFIX)
                ->update(['category_id' => $sourceId]);
        }

        foreach ($this->categories() as $category) {
            $previous = end($category['seeded']);

            if ($previous === false) {
                continue;
            }

            DB::table('categories')
                ->where('category_name', $category['name'])
                ->where('description', $category['description'])
                ->update(['description' => $previous]);
        }

        // inventory.category_id cascades on delete, so a category that still holds products is
        // kept rather than taking those products down with it.
        foreach ($this->additions() as $name) {
            $id = $this->categoryId($name);

            if ($id !== null && !DB::table('inventory')->where('category_id', $id)->exists()) {
                DB::table('categories')->where('category_id', $id)->delete();
            }
        }

        foreach ($this->renames() as $old => $new) {
            if ($this->categoryId($new) !== null && $this->categoryId($old) === null) {
                DB::table('categories')->where('category_name', $new)->update(['category_name' => $old]);
            }
        }
    }

    private function descriptionFor(string $name): string
    {
        foreach ($this->categories() as $category) {
            if ($category['name'] === $name) {
                return $category['description'];
            }
        }

        return '';
    }
};
