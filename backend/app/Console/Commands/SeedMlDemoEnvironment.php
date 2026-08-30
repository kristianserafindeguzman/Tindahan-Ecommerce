<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Store;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SeedMlDemoEnvironment extends Command
{
    protected $signature = 'ml:demo-seed';
    protected $description = 'Idempotently seeds the ML Demo Environment from the external XLSX dataset';

    private $categoryMap = [
        'Beverages' => 'Beverages',
        'Personal Care' => 'Personal Care',
        'Snacks' => 'Snacks & Sweets',
        'Bread/Bakery' => 'Snacks & Sweets',
        'Household' => 'Others',
        'Medicine' => 'Others',
        'Cigarettes' => 'Others',
        'Load/E-Load' => 'Others',
        'Condiments' => 'Cooking Essentials',
        'Canned Goods' => 'Cooking Essentials',
        'Instant Noodles' => 'Cooking Essentials',
        'Seasoning' => 'Cooking Essentials',
        'Staples' => 'Cooking Essentials',
        'Coffee' => 'Cooking Essentials',
        'Beverages/Alcohol' => 'Cooking Essentials',
    ];

    public function handle()
    {
        $this->info('Starting ML Demo Environment seed...');

        // 1. Ensure idempotency by cleaning up first
        $this->call('ml:demo-cleanup');

        $baseDir = base_path('../ml/preprocessing');
        $pythonScript = $baseDir . '/extract_sales_for_seeder.py';
        $xlsxPath = base_path('../ml/data/external/tindahan_sales_dataset.xlsx');
        $tempJson = storage_path('app/temp_tindahan_sales.json');

        // 2. Extract Data using Python
        $this->info('Extracting XLSX data via Python...');
        $process = new Process(['python', $pythonScript, '--input', $xlsxPath, '--output', $tempJson]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Data extraction failed: ' . $process->getErrorOutput());
            return 1;
        }

        if (!file_exists($tempJson)) {
            $this->error('Temp JSON file not created.');
            return 1;
        }

        $jsonData = json_decode(file_get_contents($tempJson), true);
        unlink($tempJson); // Clean up temp file

        if (empty($jsonData)) {
            $this->error('No records extracted.');
            return 1;
        }

        $this->info('Extracted ' . count($jsonData) . ' records. Beginning database insertion...');

        DB::beginTransaction();
        try {
            // 3. Create Users
            $vendor = User::create([
                'role' => 'Vendor',
                'full_name' => 'ML Demo Vendor',
                'email' => 'ml-demo@tindahan.ph',
                'phone_number' => '09990000001',
                'password_hash' => Hash::make('password123'),
                'account_status' => 'active',
            ]);

            $consumer = User::create([
                'role' => 'Consumer',
                'full_name' => 'ML Demo Consumer',
                'email' => 'ml-demo-consumer@tindahan.ph',
                'phone_number' => '09990000002',
                'password_hash' => Hash::make('password123'),
                'account_status' => 'active',
            ]);

            // 4. Create Store (and explicitly suspend it to hide from public catalog)
            $store = Store::create([
                'owner_id' => $vendor->user_id,
                'store_name' => 'ML Demonstration Store',
                'slug' => 'ml-demo-store',
                'address' => 'ML Testing Grounds',
                'opening_time' => '00:00:00',
                'closing_time' => '23:59:00',
                'latitude' => 14.5995, // Default Manila
                'longitude' => 120.9842,
                'store_picture' => 'seed-images/stores/demo.jpg',
            ]);

            // Explicitly set suspended so CatalogController filters it out
            DB::table('approval_status')->insert([
                'store_id' => $store->store_id,
                'status' => 'pending', 
                'rejection_reason' => 'Isolated ML Demo Store',
                'reviewed_at' => now(),
            ]);

            // 5. Pre-fetch Categories and Map
            $dbCategories = Category::all()->keyBy('category_name');
            $categoryIds = [];
            foreach ($this->categoryMap as $xlsxCat => $dbCatName) {
                if ($dbCategories->has($dbCatName)) {
                    $categoryIds[$xlsxCat] = $dbCategories[$dbCatName]->category_id;
                } else {
                    // Fallback to first available category if mapped one is missing, or Others
                    $fallback = $dbCategories->has('Others') ? $dbCategories['Others']->category_id : $dbCategories->first()->category_id;
                    $categoryIds[$xlsxCat] = $fallback;
                }
            }

            // 6. Calculate Date Shift
            $maxDateStr = collect($jsonData)->max('date');
            $maxDate = Carbon::parse($maxDateStr, 'Asia/Manila')->startOfDay();
            $yesterday = now('Asia/Manila')->subDay()->startOfDay();
            
            $shiftDays = $maxDate->diffInDays($yesterday, false);

            $this->info("Dataset max date is {$maxDateStr}. Shifting all dates by {$shiftDays} days to end on {$yesterday->toDateString()}.");

            // 7. Extract Unique Products & Insert Inventory
            $uniqueProducts = [];
            foreach ($jsonData as $row) {
                $pName = $row['product_name'];
                if (!isset($uniqueProducts[$pName])) {
                    $catId = $categoryIds[$row['category']] ?? $categoryIds['Others'];
                    $uniqueProducts[$pName] = [
                        'store_id' => $store->store_id,
                        'category_id' => $catId,
                        'product_name' => $pName,
                        'description' => 'ML Demo Historical Product',
                        'price' => $row['unit_price_php'],
                        'stock_quantity' => 9999, // Explicitly documented demo-only stock to prevent stockouts
                        'reserved_quantity' => 0,
                        'product_picture' => 'seed-images/products/demo.jpg',
                        'status' => 'active',
                    ];
                }
            }

            $inventoryRecords = [];
            foreach ($uniqueProducts as $p) {
                $inv = Inventory::create($p);
                $inventoryRecords[$p['product_name']] = $inv->inventory_id;
            }

            $this->info('Inserted ' . count($inventoryRecords) . ' inventory products.');

            // 8. Insert Orders and OrderItems
            $orderBatch = [];
            $orderItemBatch = [];
            
            $now = now();

            foreach ($jsonData as $i => $row) {
                // Parse original date and apply shift in Asia/Manila
                $origDate = Carbon::parse($row['date'] . ' ' . $row['time'], 'Asia/Manila');
                $shiftedDate = $origDate->addDays($shiftDays);

                $order = Order::create([
                    'consumer_id' => $consumer->user_id,
                    'store_id' => $store->store_id,
                    'total_amount' => $row['total_amount_php'],
                    'status' => 'picked_up', // Crucial for ml_historical_sales_view
                    'created_at' => $shiftedDate,
                    'updated_at' => $shiftedDate, // Used by ML view
                ]);

                $orderItemBatch[] = [
                    'order_id' => $order->order_id,
                    'inventory_id' => $inventoryRecords[$row['product_name']],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price_php'],
                    'subtotal' => $row['total_amount_php'],
                ];

                // Batch insert order items every 500 records
                if (count($orderItemBatch) >= 500) {
                    OrderItem::insert($orderItemBatch);
                    $orderItemBatch = [];
                }
            }

            if (!empty($orderItemBatch)) {
                OrderItem::insert($orderItemBatch);
            }

            DB::commit();
            $this->info("Successfully seeded ML Demo Environment with " . count($jsonData) . " transactions.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Seeding failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
