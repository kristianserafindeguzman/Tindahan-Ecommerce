<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear out any existing records first
        DB::table('users')->delete();

        // 2. Insert records matching the exact ENUM role constraints
        DB::table('users')->insert([
            [
                'full_name' => 'DTI Negosyo Admin',
                'email' => 'admin@tindahan.ph',
                'password_hash' => Hash::make('password123'),
                'role' => 'Admin',
                'phone_number' => '09123456789',
                'created_at' => now(),
            ],
            [
                'full_name' => 'Sari-Sari Store Vendor',
                'email' => 'vendor@tindahan.ph',
                'password_hash' => Hash::make('password123'),
                'role' => 'Vendor',
                'phone_number' => '09198765432',
                'created_at' => now(),
            ],
            [
                'full_name' => 'Neighborhood Consumer',
                'email' => 'consumer@tindahan.ph',
                'password_hash' => Hash::make('password123'),
                'role' => 'Consumer',
                'phone_number' => '09151112233',
                'created_at' => now(),
            ]
        ]);
    }
}