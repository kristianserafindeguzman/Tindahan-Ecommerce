<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Store;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\DemandForecast;
use Illuminate\Support\Facades\DB;

class CleanupMlDemoEnvironment extends Command
{
    protected $signature = 'ml:demo-cleanup';
    protected $description = 'Safely removes all ML Demo Environment records (Vendor, Store, Consumer, Orders, Inventory, Forecasts)';

    public function handle()
    {
        $this->info('Starting ML Demo Environment cleanup...');
        
        DB::beginTransaction();
        
        try {
            $demoVendorEmail = 'ml-demo@tindahan.ph';
            $demoConsumerEmail = 'ml-demo-consumer@tindahan.ph';
            
            $vendor = User::withTrashed()->where('email', $demoVendorEmail)->first();
            $consumer = User::withTrashed()->where('email', $demoConsumerEmail)->first();
            
            $deletedOrderCount = 0;
            $deletedInventoryCount = 0;
            $deletedForecastCount = 0;
            
            // Delete Consumer Orders
            if ($consumer) {
                $orderIds = Order::where('consumer_id', $consumer->user_id)->pluck('order_id');
                if ($orderIds->isNotEmpty()) {
                    OrderItem::whereIn('order_id', $orderIds)->delete();
                    $deletedOrderCount = Order::whereIn('order_id', $orderIds)->delete();
                }
                $consumer->forceDelete();
                $this->info('Deleted demo consumer and ' . $deletedOrderCount . ' orders.');
            }
            
            // Delete Vendor Store & Inventory
            if ($vendor) {
                $store = Store::withTrashed()->where('owner_id', $vendor->user_id)->first();
                if ($store) {
                    // Delete Demand Forecasts
                    $deletedForecastCount = DemandForecast::where('store_id', $store->store_id)->delete();
                    
                    // Delete Inventory
                    $deletedInventoryCount = Inventory::where('store_id', $store->store_id)->delete();
                    
                    // Delete Store (and cascade approval_status if applicable, though typically handled via foreign key)
                    DB::table('approval_status')->where('store_id', $store->store_id)->delete();
                    $store->forceDelete();
                }
                $vendor->forceDelete();
                $this->info("Deleted demo vendor, store, {$deletedInventoryCount} inventory items, and {$deletedForecastCount} forecasts.");
            }
            
            DB::commit();
            $this->info('ML Demo Environment cleanup completed successfully.');
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Cleanup failed: ' . $e->getMessage());
            return 1;
        }
    }
}
