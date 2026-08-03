<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the vendor's store
        $vendor = User::where('email', 'vendor@tindahan.ph')->first();
        if (!$vendor || !$vendor->store) {
            $this->command->warn('Vendor or store not found. Please run UserRoleSeeder first.');
            return;
        }
        $storeId = $vendor->store->store_id;

        // Get some categories
        $beverages = Category::where('category_name', 'Beverages')->first()->category_id ?? 1;
        $snacks = Category::where('category_name', 'Snacks')->first()->category_id ?? 2;
        $groceries = Category::where('category_name', 'Groceries')->first()->category_id ?? 3;

        $items = [
            [
                'store_id' => $storeId,
                'category_id' => $beverages,
                'product_name' => 'Coca-Cola 1.5L',
                'price' => 75.00,
                'stock_quantity' => 50,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $beverages,
                'product_name' => 'Sprite 1.5L',
                'price' => 75.00,
                'stock_quantity' => 30,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $snacks,
                'product_name' => 'Piattos Cheese',
                'price' => 16.00,
                'stock_quantity' => 100,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $snacks,
                'product_name' => 'Nova Multigrain',
                'price' => 18.00,
                'stock_quantity' => 80,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $groceries,
                'product_name' => 'Lucky Me Pancit Canton (Calamansi)',
                'price' => 15.00,
                'stock_quantity' => 200,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $groceries,
                'product_name' => 'Century Tuna Flakes in Oil',
                'price' => 35.00,
                'stock_quantity' => 60,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $groceries,
                'product_name' => 'Angel Evaporated Milk',
                'price' => 32.00,
                'stock_quantity' => 45,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ],
            [
                'store_id' => $storeId,
                'category_id' => $beverages,
                'product_name' => 'Cobra Energy Drink',
                'price' => 20.00,
                'stock_quantity' => 120,
                'reserved_quantity' => 0,
                'variants' => json_encode([]),
                'product_picture' => null,
                'status' => 'active',
            ]
        ];

        DB::table('inventory')->insert($items);
        $this->command->info('Inventory seeded for vendor@tindahan.ph');
    }
}
