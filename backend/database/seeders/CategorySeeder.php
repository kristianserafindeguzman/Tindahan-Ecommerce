<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Cooking Essentials',
                'description' => 'Cooking Oil, Condiments & Sauces, Canned Goods, Noodles, Spices & Seasonings, Rice & Grains.'
            ],
            [
                'category_name' => 'Beverages',
                'description' => 'Softdrinks & Water, Coffee & Tea, Alcoholic Drinks, Powdered Juices.'
            ],
            [
                'category_name' => 'Snacks & Sweets',
                'description' => 'Chips & Crackers, Biscuits & Cookies, Candies & Chocolates, Bread & Pastries.'
            ],
            [
                'category_name' => 'Personal Care',
                'description' => 'Bath Soap & Body Wash, Shampoo & Conditioner, Oral Care (Toothpaste/Brushes), Feminine Hygiene.'
            ],
            [
                'category_name' => 'Laundry & Cleaning',
                'description' => 'Laundry Detergents, Fabric Conditioners, Dishwashing, Household Cleaners.'
            ],
            [
                'category_name' => 'Others',
                'description' => 'OTC Medicines & First Aid, School & Office Supplies, Miscellaneous.'
            ]
        ];

        DB::table('categories')->insert($categories);
    }
}
