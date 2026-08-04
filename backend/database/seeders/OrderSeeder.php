<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the consumer and vendor's store
        $consumer = User::where('email', 'consumer@tindahan.ph')->first();
        $vendor = User::where('email', 'vendor@tindahan.ph')->first();

        if (!$consumer || !$vendor || !$vendor->store) {
            $this->command->warn('Required users or store not found.');
            return;
        }

        $storeId = $vendor->store->store_id;

        // Get inventory items for this store
        $inventory = Inventory::where('store_id', $storeId)->get();

        if ($inventory->count() < 2) {
            $this->command->warn('Not enough inventory items. Run InventorySeeder first.');
            return;
        }

        // Define mock orders
        $orderTemplates = [];
        $statuses = ['placed', 'preparing', 'ready_for_pickup', 'picked_up', 'picked_up', 'picked_up', 'cancelled'];
        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $orderTemplates[] = [
                'status' => $status,
                'created_at' => Carbon::now()->subDays(rand(0, 7))->subHours(rand(0, 23))->subMinutes(rand(0, 59))
            ];
        }

        foreach ($orderTemplates as $index => $template) {
            // Pick 2 random items
            $items = $inventory->random(2);
            $totalAmount = 0;

            // Create Order
            $order = Order::create([
                'consumer_id' => $consumer->user_id,
                'store_id' => $storeId,
                'total_amount' => 0, // Will update after items
                'status' => $template['status'],
                'created_at' => $template['created_at'],
                'updated_at' => $template['created_at'],
            ]);

            foreach ($items as $item) {
                $qty = rand(1, 3);
                $subtotal = $qty * $item->price;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'inventory_id' => $item->inventory_id,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ]);

                // Reservation logic for dummy data:
                // Only 'placed', 'preparing', 'ready_for_pickup' have reservations holding stock
                if (in_array($template['status'], ['placed', 'preparing', 'ready_for_pickup'])) {
                    $item->reserved_quantity += $qty;
                    $item->save();
                }
            }

            $order->total_amount = $totalAmount;
            $order->timestamps = false;
            $order->save();
        }

        $this->command->info('Mock orders seeded for vendor@tindahan.ph');
    }
}
