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
                ['name' => 'Rice 1kg', 'price' => 50, 'pic' => 'seed-images/products/rice.jpg'],
                ['name' => 'Cooking Oil', 'price' => 120, 'pic' => 'seed-images/products/cooking-oil.jpg', 'variants' => [['quantity' => 20, 'price' => 60, 'name' => '500ml'], ['quantity' => 15, 'price' => 120, 'name' => '1L']]],
                ['name' => 'Soy Sauce', 'price' => 45, 'pic' => 'seed-images/products/soy-sauce.jpg'],
                ['name' => 'Vinegar', 'price' => 40, 'pic' => 'seed-images/products/vinegar.jpg'],
                ['name' => 'Corned Beef', 'price' => 65, 'pic' => 'seed-images/products/canned-goods.jpg'],
                ['name' => 'Lucky Me Pancit Canton', 'price' => 15, 'pic' => 'seed-images/products/instant-noodles.jpg'],
                ['name' => 'Rock Salt', 'price' => 20, 'pic' => 'seed-images/products/salt.jpg'],
                ['name' => 'Black Pepper', 'price' => 5, 'pic' => 'seed-images/products/pepper.jpg'],
            ],
            'Beverages' => [
                ['name' => 'Coca-Cola', 'price' => 75, 'pic' => 'seed-images/products/coke.jpg', 'variants' => [['quantity' => 10, 'price' => 75, 'name' => '1.5L'], ['quantity' => 20, 'price' => 25, 'name' => 'Mismo']]],
                ['name' => 'Sprite', 'price' => 75, 'pic' => 'seed-images/products/sprite.jpg'],
                ['name' => 'Royal', 'price' => 75, 'pic' => 'seed-images/products/royal.jpg'],
                ['name' => 'Mineral Water', 'price' => 15, 'pic' => 'seed-images/products/water.jpg', 'variants' => [['quantity' => 30, 'price' => 15, 'name' => '350ml'], ['quantity' => 25, 'price' => 20, 'name' => '500ml'], ['quantity' => 10, 'price' => 35, 'name' => '1L']]],
                ['name' => 'Nescafe Classic', 'price' => 85, 'pic' => 'seed-images/products/coffee.jpg', 'variants' => [['quantity' => 10, 'price' => 85, 'name' => 'Small'], ['quantity' => 15, 'price' => 150, 'name' => 'Large']]],
                ['name' => 'Bear Brand Powdered Milk', 'price' => 95, 'pic' => 'seed-images/products/milk.jpg'],
                ['name' => 'Tang Orange Juice', 'price' => 22, 'pic' => 'seed-images/products/juice.jpg'],
            ],
            'Snacks & Sweets' => [
                ['name' => 'Piattos Cheese', 'price' => 18, 'pic' => 'seed-images/products/piattos.jpg'],
                ['name' => 'Nova Multigrain', 'price' => 18, 'pic' => 'seed-images/products/nova.jpg'],
                ['name' => 'SkyFlakes Crackers', 'price' => 60, 'pic' => 'seed-images/products/skyflakes.jpg'],
                ['name' => 'Oreo Vanilla', 'price' => 45, 'pic' => 'seed-images/products/oreo.jpg'],
                ['name' => 'Gardenia White Bread', 'price' => 75, 'pic' => 'seed-images/products/bread.jpg'],
                ['name' => 'Cloud 9 Chocolate', 'price' => 12, 'pic' => 'seed-images/products/chocolate.jpg'],
            ],
            'Personal Care' => [
                ['name' => 'Safeguard White', 'price' => 40, 'pic' => 'seed-images/products/safeguard.jpg'],
                ['name' => 'Palmolive Naturals', 'price' => 35, 'pic' => 'seed-images/products/palmolive.jpg'],
                ['name' => 'Sunsilk Shampoo', 'price' => 7, 'pic' => 'seed-images/products/shampoo.jpg'],
                ['name' => 'Colgate Toothpaste', 'price' => 85, 'pic' => 'seed-images/products/toothpaste.jpg'],
                ['name' => 'Oral-B Toothbrush', 'price' => 65, 'pic' => 'seed-images/products/toothbrush.jpg'],
            ],
            'Laundry & Cleaning' => [
                ['name' => 'Surf Cherry Blossom', 'price' => 15, 'pic' => 'seed-images/products/surf.jpg', 'variants' => [['quantity' => 50, 'price' => 15, 'name' => '500g'], ['quantity' => 10, 'price' => 120, 'name' => '1kg']]],
                ['name' => 'Ariel Sunrise Fresh', 'price' => 20, 'pic' => 'seed-images/products/ariel.jpg', 'variants' => [['quantity' => 30, 'price' => 20, 'name' => '500g'], ['quantity' => 15, 'price' => 130, 'name' => '1kg']]],
                ['name' => 'Joy Dishwashing Liquid', 'price' => 12, 'pic' => 'seed-images/products/joy.jpg'],
                ['name' => 'Zonrox Bleach', 'price' => 30, 'pic' => 'seed-images/products/zonrox.jpg'],
                ['name' => 'Downy Antibac', 'price' => 8, 'pic' => 'seed-images/products/fabric-conditioner.jpg'],
            ],
            'Others' => [
                ['name' => 'Green Cross Alcohol', 'price' => 50, 'pic' => 'seed-images/products/alcohol.jpg'],
                ['name' => 'Face Mask 50pcs', 'price' => 45, 'pic' => 'seed-images/products/face-mask.jpg'],
                ['name' => 'Hard Copy Bond Paper A4', 'price' => 180, 'pic' => 'seed-images/products/bond-paper.jpg'],
                ['name' => 'Panda Ballpen Black', 'price' => 8, 'pic' => 'seed-images/products/ballpen.jpg'],
                ['name' => 'Energizer AA Batteries', 'price' => 120, 'pic' => 'seed-images/products/batteries.jpg'],
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
