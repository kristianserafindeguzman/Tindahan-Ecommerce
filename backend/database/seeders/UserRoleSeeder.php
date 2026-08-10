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
                'full_name'        => 'Monica Geller',
                'email'            => 'vendor2@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Vendor',
                'phone_number'     => '09171112233',
                'account_status'   => 'active',
                'last_activity_at' => now()->subHours(2),
                'created_at'       => now()->subDays(3),
            ],
            [
                'full_name'        => 'Leslie Victoria',
                'email'            => 'vendor3@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Vendor',
                'phone_number'     => '09175556677',
                'account_status'   => 'active',
                'last_activity_at' => now()->subHours(5),
                'created_at'       => now()->subDays(1),
            ],
            [
                'full_name'        => 'Chandler Bing',
                'email'            => 'consumer@tindahan.ph',
                'password_hash'    => Hash::make('password123'),
                'role'             => 'Consumer',
                'phone_number'     => '09151112233',
                'account_status'   => 'active',
                'last_activity_at' => now()->subMinutes(5),
                'created_at'       => now()->subDays(14),
            ],
        ]);

        $admin = DB::table('users')->where('email', 'admin@tindahan.ph')->first();
        
        $vendors = [
            'vendor@tindahan.ph' => [
                'store_name'    => 'Aling Nena Sari-Sari Store',
                'store_picture' => 'seed-images/stores/aling-nena.jpg',
                'address'       => '123 Mandaluyong Street, Metro Manila',
                'opening_time'  => '06:00',
                'closing_time'  => '22:00',
                'operating_days'=> json_encode([
                    'Monday'    => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Tuesday'   => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Wednesday' => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Thursday'  => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Friday'    => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Saturday'  => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                    'Sunday'    => ['is_open' => true, 'opening_time' => '06:00', 'closing_time' => '22:00'],
                ]),
                'latitude'      => 14.5764, 
                'longitude'     => 121.0351, 
            ],
            'vendor2@tindahan.ph' => [
                'store_name'    => 'Monica Store',
                'store_picture' => 'seed-images/stores/monica-store.jpg',
                'address'       => '456 Quezon Avenue, Quezon City',
                'opening_time'  => '07:00',
                'closing_time'  => '21:00',
                'operating_days'=> json_encode([
                    'Monday'    => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Tuesday'   => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Wednesday' => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Thursday'  => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Friday'    => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Saturday'  => ['is_open' => true, 'opening_time' => '07:00', 'closing_time' => '21:00'],
                    'Sunday'    => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
                ]),
                'latitude'      => 14.5900, 
                'longitude'     => 121.0200, 
            ],
            'vendor3@tindahan.ph' => [
                'store_name'    => 'Leslie Local Produce',
                'store_picture' => 'seed-images/stores/leslie-produce.jpg',
                'address'       => '789 Makati Avenue, Makati City',
                'opening_time'  => '08:00',
                'closing_time'  => '18:00',
                'operating_days'=> json_encode([
                    'Monday'    => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '18:00'],
                    'Tuesday'   => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '18:00'],
                    'Wednesday' => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '18:00'],
                    'Thursday'  => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '18:00'],
                    'Friday'    => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '18:00'],
                    'Saturday'  => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
                    'Sunday'    => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
                ]),
                'latitude'      => 14.5547, 
                'longitude'     => 121.0244, 
            ],
        ];

        foreach ($vendors as $email => $storeData) {
            $vendor = DB::table('users')->where('email', $email)->first();
            
            $storeId = DB::table('stores')->insertGetId(array_merge([
                'owner_id' => $vendor->user_id,
            ], $storeData));

            // Auto-approve every store
            DB::table('approval_status')->insert([
                'store_id'         => $storeId,
                'admin_id'         => $admin->user_id,
                'status'           => 'approved',
                'rejection_reason' => null,
                'reviewed_at'      => now()->subDays(rand(1, 5)),
            ]);
        }
    }
}