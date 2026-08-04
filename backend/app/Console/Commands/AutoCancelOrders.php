<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoCancelOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel orders stuck in placed status for more than 60 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::with('items')
            ->where('status', 'placed')
            ->where('created_at', '<', Carbon::now()->subMinutes(60))
            ->get();

        $count = 0;

        foreach ($expiredOrders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    // Revert reservation
                    foreach ($order->items as $item) {
                        $inventory = Inventory::where('inventory_id', $item->inventory_id)->lockForUpdate()->first();
                        if ($inventory) {
                            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                            $inventory->save();
                        }
                    }

                    $order->status = 'cancelled';
                    $order->save();
                });
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to cancel order ID {$order->order_id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully cancelled {$count} expired orders.");
    }
}
