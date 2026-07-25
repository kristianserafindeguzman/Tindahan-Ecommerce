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
        // 1. Clear out any existing records first (order matters for FK constraints)
        DB::table('approval_status')->delete();
        DB::table('inventory')->delete();
        DB::table('stores')->delete();
        DB::table('otp_codes')->delete();
        DB::table('personal_access_tokens')->delete();
        DB::table('users')->delete();

        // 2. Insert users
        DB::table('users')->insert([
            [
                'full_name'        => 'DTI Negosyo Admin',
                'email'            => 'admin@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Admin',
                'phone_number'     => '09123456789',
                'account_status'   => 'active',
                'last_activity_at' => now(),
                'created_at'       => now(),
            ],
            [
                'full_name'        => 'Aling Nena',
                'email'            => 'vendor@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Vendor',
                'phone_number'     => '09198765432',
                'account_status'   => 'active',
                'last_activity_at' => now()->subMinutes(30),
                'created_at'       => now()->subDays(7),
            ],
            [
                'full_name'        => 'Mang Juan Store',
                'email'            => 'vendor2@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Vendor',
                'phone_number'     => '09171112233',
                'account_status'   => 'active',
                'last_activity_at' => now()->subHours(2),
                'created_at'       => now()->subDays(3),
            ],
            [
                'full_name'        => 'Juan dela Cruz',
                'email'            => 'consumer@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Consumer',
                'phone_number'     => '09151112233',
                'account_status'   => 'active',
                'last_activity_at' => now()->subMinutes(5),
                'created_at'       => now()->subDays(14),
            ],
            [
                'full_name'        => 'Maria Santos',
                'email'            => 'consumer2@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Consumer',
                'phone_number'     => '09161234567',
                'account_status'   => 'active',
                'last_activity_at' => now()->subHours(1),
                'created_at'       => now()->subDays(10),
            ],
        ]);

        // 3. Create a store for vendor@tindahan.ph (APPROVED — can login to dashboard)
        $vendor1 = DB::table('users')->where('email', 'vendor@tindahan.ph')->first();
        $admin   = DB::table('users')->where('email', 'admin@tindahan.ph')->first();

        $storeId1 = DB::table('stores')->insertGetId([
            'owner_id'      => $vendor1->user_id,
            'store_name'    => 'Aling Nena Sari-Sari Store',
            'store_picture'  => 'stores/placeholder.jpg',
            'opening_time'  => '06:00',
            'closing_time'  => '22:00',
            'latitude'      => 14.5764,
            'longitude'     => 121.0351,
        ]);

        DB::table('approval_status')->insert([
            'store_id'         => $storeId1,
            'admin_id'         => $admin->user_id,
            'status'           => 'approved',
            'rejection_reason' => null,
            'reviewed_at'      => now()->subDays(5),
        ]);

        // 4. Create a store for vendor2@tindahan.ph (PENDING — for testing admin approvals)
        $vendor2 = DB::table('users')->where('email', 'vendor2@tindahan.ph')->first();

        $storeId2 = DB::table('stores')->insertGetId([
            'owner_id'      => $vendor2->user_id,
            'store_name'    => 'Mang Juan Variety Store',
            'store_picture'  => 'stores/placeholder.jpg',
            'opening_time'  => '07:00',
            'closing_time'  => '21:00',
            'latitude'      => 14.5900,
            'longitude'     => 121.0200,
        ]);

        DB::table('approval_status')->insert([
            'store_id'         => $storeId2,
            'admin_id'         => null,
            'status'           => 'pending',
            'rejection_reason' => null,
            'reviewed_at'      => null,
        ]);
    }
}