<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * The first six are the original categories and must keep their order, since their
     * category_id values are already referenced by seeded inventory.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Cooking Essentials',
                'description' => 'Cooking oil, sugar, salt, pepper, spices, seasonings, garlic, onion, and basic cooking ingredients.'
            ],
            [
                'category_name' => 'Beverages',
                'description' => 'Bottled water, softdrinks, juice, energy drinks, sports drinks, and alcoholic drinks.'
            ],
            [
                'category_name' => 'Snacks & Sweets',
                'description' => 'Chips, crackers, biscuits, cookies, wafers, nuts, and chicharon.'
            ],
            [
                'category_name' => 'Personal Care',
                'description' => 'Bath soap, body wash, shampoo, conditioner, deodorant, razors, sanitary napkins, and cotton buds.'
            ],
            [
                'category_name' => 'Laundry & Cleaning',
                'description' => 'Laundry detergent, fabric conditioner, bleach, dishwashing liquid, and toilet and floor cleaners.'
            ],
            [
                'category_name' => 'Others',
                'description' => 'Miscellaneous items not covered by the other categories.'
            ],
            [
                'category_name' => 'Rice & Grains',
                'description' => 'Rice, corn, oats, and other grains sold per kilo or per pack.'
            ],
            [
                'category_name' => 'Canned & Packaged Foods',
                'description' => 'Sardines, canned tuna, corned beef, luncheon meat, and other canned goods.'
            ],
            [
                'category_name' => 'Instant Noodles & Pasta',
                'description' => 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, and pasta.'
            ],
            [
                'category_name' => 'Condiments & Sauces',
                'description' => 'Soy sauce, vinegar, fish sauce, ketchup, mayonnaise, seasoning mixes, and spreads.'
            ],
            [
                'category_name' => 'Chilled & Processed Foods',
                'description' => 'Hotdog, longganisa, tocino, cheese, margarine, and eggs.'
            ],
            [
                'category_name' => 'Coffee, Milk & Breakfast Drinks',
                'description' => 'Coffee, 3-in-1 coffee sachets, powdered and condensed milk, and chocolate drinks.'
            ],
            [
                'category_name' => 'Bread & Bakery',
                'description' => 'Pandesal, sliced bread, loaf bread, buns, and other bakery items.'
            ],
            [
                'category_name' => 'Candies & Chocolates',
                'description' => 'Candies, chocolates, mints, and other sweets.'
            ],
            [
                'category_name' => 'Oral Care',
                'description' => 'Toothpaste, toothbrushes, mouthwash, and dental floss.'
            ],
            [
                'category_name' => 'Baby Care',
                'description' => 'Diapers, baby soap, baby powder, baby wipes, and baby milk.'
            ],
            [
                'category_name' => 'OTC Medicine & First Aid',
                'description' => 'Over-the-counter medicines, pain relievers, cold and flu tablets, antacids, bandages, antiseptics, alcohol, and face masks.'
            ],
            [
                'category_name' => 'School & Office Supplies',
                'description' => 'Ballpens, pencils, paper, notebooks, envelopes, and other school and office supplies.'
            ],
            [
                'category_name' => 'Household Supplies',
                'description' => 'Tissue, trash bags, plastic bags, insect spray, brooms, and other household needs.'
            ],
            [
                'category_name' => 'Batteries, Lighting & Electrical',
                'description' => 'Batteries, light bulbs, candles, matches, extension cords, and small electrical supplies.'
            ],
            [
                'category_name' => 'Mobile Load & E-Services',
                'description' => 'Prepaid load, e-load, mobile data promos, and e-wallet cash-in services.'
            ],
            [
                'category_name' => 'Cigarettes & Tobacco',
                'description' => 'Cigarettes sold per stick or per pack, and other tobacco products.'
            ]
        ];

        DB::table('categories')->insert($categories);
    }
}
