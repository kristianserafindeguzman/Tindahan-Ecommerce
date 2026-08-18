<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = [
            'Cooking Essentials' => [
                ['name' => 'Rice 1kg', 'description' => 'Everyday milled rice for cooking meals.', 'price' => 50, 'pic' => 'seed-images/products/rice.jpg'],
                ['name' => 'Cooking Oil', 'description' => 'Cooking oil commonly used for frying and food preparation.', 'price' => 120, 'pic' => 'seed-images/products/cooking-oil.jpg', 'variants' => [['quantity' => 20, 'price' => 60, 'name' => '500ml'], ['quantity' => 15, 'price' => 120, 'name' => '1L']]],
                ['name' => 'Soy Sauce', 'description' => 'Seasoning sauce for marinades and cooking.', 'price' => 45, 'pic' => 'seed-images/products/soy-sauce.jpg'],
                ['name' => 'Vinegar', 'description' => 'Vinegar for cooking and food preparation.', 'price' => 40, 'pic' => 'seed-images/products/vinegar.jpg'],
                ['name' => 'Corned Beef', 'description' => 'Canned corned beef ready to cook and serve.', 'price' => 65, 'pic' => 'seed-images/products/canned-goods.jpg'],
                ['name' => 'Lucky Me Pancit Canton', 'description' => 'Instant stir-fry noodles with seasoning.', 'price' => 15, 'pic' => 'seed-images/products/instant-noodles.jpg'],
                ['name' => 'Rock Salt', 'description' => 'Coarse rock salt for cooking and seasoning.', 'price' => 20, 'pic' => 'seed-images/products/salt.jpg'],
                ['name' => 'Black Pepper', 'description' => 'Ground black pepper for seasoning dishes.', 'price' => 5, 'pic' => 'seed-images/products/pepper.jpg'],
            ],
            'Beverages' => [
                ['name' => 'Coca-Cola', 'description' => 'Carbonated cola soft drink.', 'price' => 75, 'pic' => 'seed-images/products/coke.jpg', 'variants' => [['quantity' => 10, 'price' => 75, 'name' => '1.5L'], ['quantity' => 20, 'price' => 25, 'name' => 'Mismo']]],
                ['name' => 'Sprite', 'description' => 'Lemon-lime flavored carbonated soft drink.', 'price' => 75, 'pic' => 'seed-images/products/sprite.jpg'],
                ['name' => 'Royal', 'description' => 'Orange-flavored carbonated soft drink.', 'price' => 75, 'pic' => 'seed-images/products/royal.jpg'],
                ['name' => 'Mineral Water', 'description' => 'Purified drinking water.', 'price' => 15, 'pic' => 'seed-images/products/water.jpg', 'variants' => [['quantity' => 30, 'price' => 15, 'name' => '350ml'], ['quantity' => 25, 'price' => 20, 'name' => '500ml'], ['quantity' => 10, 'price' => 35, 'name' => '1L']]],
                ['name' => 'Nescafe Classic', 'description' => 'Instant coffee for hot or iced preparation.', 'price' => 85, 'pic' => 'seed-images/products/coffee.jpg', 'variants' => [['quantity' => 10, 'price' => 85, 'name' => 'Small'], ['quantity' => 15, 'price' => 150, 'name' => 'Large']]],
                ['name' => 'Bear Brand Powdered Milk', 'description' => 'Powdered milk for drinking and cooking.', 'price' => 95, 'pic' => 'seed-images/products/milk.jpg'],
                ['name' => 'Tang Orange Juice', 'description' => 'Powdered orange juice drink mix.', 'price' => 22, 'pic' => 'seed-images/products/juice.jpg'],
            ],
            'Snacks & Sweets' => [
                ['name' => 'Piattos Cheese', 'description' => 'Cheese-flavored potato crisps snack.', 'price' => 18, 'pic' => 'seed-images/products/piattos.jpg'],
                ['name' => 'Nova Multigrain', 'description' => 'Multigrain snack chips.', 'price' => 18, 'pic' => 'seed-images/products/nova.jpg'],
                ['name' => 'SkyFlakes Crackers', 'description' => 'Plain salted crackers for snacking.', 'price' => 60, 'pic' => 'seed-images/products/skyflakes.jpg'],
                ['name' => 'Oreo Vanilla', 'description' => 'Vanilla cream-filled chocolate sandwich cookies.', 'price' => 45, 'pic' => 'seed-images/products/oreo.jpg'],
                ['name' => 'Gardenia White Bread', 'description' => 'Sliced white bread for sandwiches and toast.', 'price' => 75, 'pic' => 'seed-images/products/bread.jpg'],
                ['name' => 'Cloud 9 Chocolate', 'description' => 'Chocolate-coated wafer candy bar.', 'price' => 12, 'pic' => 'seed-images/products/chocolate.jpg'],
            ],
            'Personal Care' => [
                ['name' => 'Safeguard White', 'description' => 'Antibacterial bath soap bar.', 'price' => 40, 'pic' => 'seed-images/products/safeguard.jpg'],
                ['name' => 'Palmolive Naturals', 'description' => 'Moisturizing bath soap with natural extracts.', 'price' => 35, 'pic' => 'seed-images/products/palmolive.jpg'],
                ['name' => 'Sunsilk Shampoo', 'description' => 'Hair shampoo for daily use.', 'price' => 7, 'pic' => 'seed-images/products/shampoo.jpg'],
                ['name' => 'Colgate Toothpaste', 'description' => 'Toothpaste for daily oral cleaning and freshness.', 'price' => 85, 'pic' => 'seed-images/products/toothpaste.jpg'],
                ['name' => 'Oral-B Toothbrush', 'description' => 'Toothbrush for daily dental hygiene.', 'price' => 65, 'pic' => 'seed-images/products/toothbrush.jpg'],
            ],
            'Laundry & Cleaning' => [
                ['name' => 'Surf Cherry Blossom', 'description' => 'Laundry detergent powder with cherry blossom scent.', 'price' => 15, 'pic' => 'seed-images/products/surf.jpg', 'variants' => [['quantity' => 50, 'price' => 15, 'name' => '500g'], ['quantity' => 10, 'price' => 120, 'name' => '1kg']]],
                ['name' => 'Ariel Sunrise Fresh', 'description' => 'Laundry detergent powder with fresh scent.', 'price' => 20, 'pic' => 'seed-images/products/ariel.jpg', 'variants' => [['quantity' => 30, 'price' => 20, 'name' => '500g'], ['quantity' => 15, 'price' => 130, 'name' => '1kg']]],
                ['name' => 'Joy Dishwashing Liquid', 'description' => 'Liquid dish soap for washing dishes.', 'price' => 12, 'pic' => 'seed-images/products/joy.jpg'],
                ['name' => 'Zonrox Bleach', 'description' => 'Household bleach for cleaning and disinfecting.', 'price' => 30, 'pic' => 'seed-images/products/zonrox.jpg'],
                ['name' => 'Downy Antibac', 'description' => 'Fabric conditioner with antibacterial formula.', 'price' => 8, 'pic' => 'seed-images/products/fabric-conditioner.jpg'],
            ],
            'Others' => [
                ['name' => 'Green Cross Alcohol', 'description' => 'Isopropyl rubbing alcohol for sanitizing.', 'price' => 50, 'pic' => 'seed-images/products/alcohol.jpg'],
                ['name' => 'Face Mask 50pcs', 'description' => 'Disposable face masks, pack of 50.', 'price' => 45, 'pic' => 'seed-images/products/face-mask.jpg'],
                ['name' => 'Hard Copy Bond Paper A4', 'description' => 'A4 bond paper for printing and writing.', 'price' => 180, 'pic' => 'seed-images/products/bond-paper.jpg'],
                ['name' => 'Panda Ballpen Black', 'description' => 'Black ballpoint pen for writing.', 'price' => 8, 'pic' => 'seed-images/products/ballpen.jpg'],
                ['name' => 'Energizer AA Batteries', 'description' => 'AA alkaline batteries for devices.', 'price' => 120, 'pic' => 'seed-images/products/batteries.jpg'],
            ],
        ];

        // Resolve real category_id values by name instead of assuming CategorySeeder's insert order.
        $categoryIds = DB::table('categories')->pluck('category_id', 'category_name');

        $flatCatalog = [];
        foreach ($catalog as $categoryName => $products) {
            if (!$categoryIds->has($categoryName)) {
                $this->command->warn("Category \"{$categoryName}\" not found. Please run CategorySeeder first.");
                continue;
            }

            foreach ($products as $product) {
                $product['category_id'] = $categoryIds[$categoryName];
                $flatCatalog[] = $product;
            }
        }

        $stores = DB::table('stores')->get();

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run UserRoleSeeder first.');
            return;
        }

        foreach ($stores as $store) {
            // Give each store 20-30 random products
            $shuffled = $flatCatalog;
            shuffle($shuffled);
            $selectedProducts = array_slice($shuffled, 0, rand(20, 30));

            foreach ($selectedProducts as $product) {
                $price = $product['price'];
                $variants = isset($product['variants']) ? json_encode($product['variants']) : null;
                $stock = rand(10, 100);

                // If it has variants, auto-calculate stock and minimum price based on standard logic
                if (isset($product['variants'])) {
                    $stock = 0;
                    $price = $product['variants'][0]['price'];
                    foreach($product['variants'] as $v) {
                        $stock += $v['quantity'];
                        if ($v['price'] < $price) {
                            $price = $v['price'];
                        }
                    }
                }

                DB::table('inventory')->insert([
                    'store_id' => $store->store_id,
                    'category_id' => $product['category_id'],
                    'product_name' => $product['name'],
                    'description' => $product['description'] ?? null,
                    'price' => $price,
                    'stock_quantity' => $stock,
                    'reserved_quantity' => 0,
                    'variants' => $variants,
                    'product_picture' => $product['pic'],
                    'status' => 'active',
                ]);
            }
        }

        $this->command->info('Inventory seeded for all stores with realistic data.');
    }
}
