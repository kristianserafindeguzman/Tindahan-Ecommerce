<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $descriptions = [
            'Rice 1kg' => 'Everyday milled rice for cooking meals.',
            'Cooking Oil' => 'Cooking oil commonly used for frying and food preparation.',
            'Soy Sauce' => 'Seasoning sauce for marinades and cooking.',
            'Vinegar' => 'Vinegar for cooking and food preparation.',
            'Corned Beef' => 'Canned corned beef ready to cook and serve.',
            'Lucky Me Pancit Canton' => 'Instant stir-fry noodles with seasoning.',
            'Rock Salt' => 'Coarse rock salt for cooking and seasoning.',
            'Black Pepper' => 'Ground black pepper for seasoning dishes.',
            'Coca-Cola' => 'Carbonated cola soft drink.',
            'Sprite' => 'Lemon-lime flavored carbonated soft drink.',
            'Royal' => 'Orange-flavored carbonated soft drink.',
            'Mineral Water' => 'Purified drinking water.',
            'Nescafe Classic' => 'Instant coffee for hot or iced preparation.',
            'Bear Brand Powdered Milk' => 'Powdered milk for drinking and cooking.',
            'Tang Orange Juice' => 'Powdered orange juice drink mix.',
            'Piattos Cheese' => 'Cheese-flavored potato crisps snack.',
            'Nova Multigrain' => 'Multigrain snack chips.',
            'SkyFlakes Crackers' => 'Plain salted crackers for snacking.',
            'Oreo Vanilla' => 'Vanilla cream-filled chocolate sandwich cookies.',
            'Gardenia White Bread' => 'Sliced white bread for sandwiches and toast.',
            'Cloud 9 Chocolate' => 'Chocolate-coated wafer candy bar.',
            'Safeguard White' => 'Antibacterial bath soap bar.',
            'Palmolive Naturals' => 'Moisturizing bath soap with natural extracts.',
            'Sunsilk Shampoo' => 'Hair shampoo for daily use.',
            'Colgate Toothpaste' => 'Toothpaste for daily oral cleaning and freshness.',
            'Oral-B Toothbrush' => 'Toothbrush for daily dental hygiene.',
            'Surf Cherry Blossom' => 'Laundry detergent powder with cherry blossom scent.',
            'Ariel Sunrise Fresh' => 'Laundry detergent powder with fresh scent.',
            'Joy Dishwashing Liquid' => 'Liquid dish soap for washing dishes.',
            'Zonrox Bleach' => 'Household bleach for cleaning and disinfecting.',
            'Downy Antibac' => 'Fabric conditioner with antibacterial formula.',
            'Green Cross Alcohol' => 'Isopropyl rubbing alcohol for sanitizing.',
            'Face Mask 50pcs' => 'Disposable face masks, pack of 50.',
            'Hard Copy Bond Paper A4' => 'A4 bond paper for printing and writing.',
            'Panda Ballpen Black' => 'Black ballpoint pen for writing.',
            'Energizer AA Batteries' => 'AA alkaline batteries for devices.'
        ];

        foreach ($descriptions as $name => $desc) {
            DB::table('inventory')
                ->where('product_name', $name)
                ->whereNull('description')
                ->update(['description' => $desc]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To be safe, we don't automatically unset descriptions in down(),
        // as vendors might have edited them. Or we can just leave it empty.
    }
};
