<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrite the seeded category subtexts in plain language.
     *
     * The originals were comma-separated shorthand ("OTC Medicines & First Aid") that shoppers and
     * new vendors had to decode. Each row is only touched while it still holds the seeded text, so
     * a description a vendor has since edited is left exactly as they wrote it.
     */
    private function rows(): array
    {
        return [
            [
                'name' => 'Cooking Essentials',
                'old'  => 'Cooking Oil, Condiments & Sauces, Canned Goods, Noodles, Spices & Seasonings, Rice & Grains.',
                'new'  => 'Ingredients for cooking a meal: rice, cooking oil, salt, pepper and other seasonings, soy sauce, vinegar, fish sauce and other condiments, canned goods, and instant noodles.',
            ],
            [
                'name' => 'Beverages',
                'old'  => 'Softdrinks & Water, Coffee & Tea, Alcoholic Drinks, Powdered Juices.',
                'new'  => 'Anything you drink: bottled and mineral water, softdrinks, powdered and bottled juice, coffee, tea, powdered and canned milk, and alcoholic drinks like beer and gin.',
            ],
            [
                'name' => 'Snacks & Sweets',
                'old'  => 'Chips & Crackers, Biscuits & Cookies, Candies & Chocolates, Bread & Pastries.',
                'new'  => 'Food eaten between meals: chips and crackers, biscuits and cookies, candies and chocolates, peanuts, and bread and pastries.',
            ],
            [
                'name' => 'Personal Care',
                'old'  => 'Bath Soap & Body Wash, Shampoo & Conditioner, Oral Care (Toothpaste/Brushes), Feminine Hygiene.',
                'new'  => 'Things you use on your body: bath soap and body wash, shampoo and conditioner, toothpaste and toothbrushes, deodorant, razors, and napkins and other feminine hygiene needs.',
            ],
            [
                'name' => 'Laundry & Cleaning',
                'old'  => 'Laundry Detergents, Fabric Conditioners, Dishwashing, Household Cleaners.',
                'new'  => 'For washing clothes and cleaning the house: powder and bar laundry detergent, fabric conditioner, dishwashing liquid and sponges, bleach, and floor, toilet and glass cleaners.',
            ],
            [
                'name' => 'Others',
                'old'  => 'OTC Medicines & First Aid, School & Office Supplies, Miscellaneous.',
                'new'  => 'Anything that does not fit the other categories: over-the-counter medicine such as paracetamol, first-aid supplies like bandages and alcohol, school and office supplies such as pens, paper and notebooks, plus batteries, candles and load cards.',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->rows() as $row) {
            DB::table('categories')
                ->where('category_name', $row['name'])
                ->where('description', $row['old'])
                ->update(['description' => $row['new']]);
        }
    }

    public function down(): void
    {
        foreach ($this->rows() as $row) {
            DB::table('categories')
                ->where('category_name', $row['name'])
                ->where('description', $row['new'])
                ->update(['description' => $row['old']]);
        }
    }
};
