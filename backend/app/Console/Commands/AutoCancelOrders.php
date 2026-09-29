<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\Notification;
use App\Services\CartReservationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Automatically cancel expired cart items, vendor unaccepted orders, and unpicked orders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->cleanupExpiredCarts();
        $this->cancelUnpreparedOrders();
        $this->cancelUnpickedOrders();
    }

    private function cleanupExpiredCarts()
    {
        // Shared with the cart and checkout controllers, so a reservation is released the same way
        // whether this job gets there first or a shopper touches the product.
        $released = app(CartReservationService::class)->releaseAllExpired();

        if ($released > 0) {
            $this->info("Released {$released} reserved unit(s) from expired cart items.");
        }
    }

    private function cancelUnpreparedOrders()
    {
        $prepMinutes = config('tindahan.order_preparation_minutes', 180);
        $expiredOrders = Order::with('items.inventory', 'store')
            ->where('status', 'placed')
            ->where('created_at', '<', Carbon::now()->subMinutes($prepMinutes))
            ->get();

        $this->cancelOrders($expiredOrders, 'Vendor did not prepare the order in time.');
    }

    private function cancelUnpickedOrders()
    {
        $pickupMinutes = config('tindahan.order_pickup_minutes', 720);
        $expiredOrders = Order::with('items.inventory', 'store')
            ->where('status', 'ready_for_pickup')
            ->whereNotNull('ready_for_pickup_at')
            ->where('ready_for_pickup_at', '<', Carbon::now()->subMinutes($pickupMinutes))
            ->get();

        $this->cancelOrders($expiredOrders, 'Consumer did not pick up the order in time.');
    }

    private function cancelOrders($orders, $reason)
    {
        $count = 0;

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order, $reason) {
                    $lockedOrder = Order::where('order_id', $order->order_id)->lockForUpdate()->first();
                    if (!$lockedOrder || in_array($lockedOrder->status, ['cancelled', 'picked_up'])) {
                        return; // Already processed
                    }

                    // Revert reservation
                    foreach ($order->items as $item) {
                        $inventory = Inventory::where('inventory_id', $item->inventory_id)->lockForUpdate()->first();
                        if ($inventory) {
                            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $item->quantity);
                            $inventory->save();
                        }
                    }

                    $lockedOrder->status = 'cancelled';
                    $lockedOrder->cancellation_reason = $reason;
                    $lockedOrder->save();
                    
                    // Notifications
                    try {
                        // Consumer
                        Notification::create([
                            'user_id' => $lockedOrder->consumer_id,
                            'order_id' => $lockedOrder->order_id,
                            'title' => 'Order Cancelled',
                            'message' => "Your order #{$lockedOrder->order_id} was cancelled. Reason: {$reason}",
                        ]);

                        // Vendor
                        $ownerId = optional($order->store)->owner_id;
                        if ($ownerId) {
                            Notification::create([
                                'user_id' => $ownerId,
                                'order_id' => $lockedOrder->order_id,
                                'title' => 'Order Cancelled Automatically',
                                'message' => "Order #{$lockedOrder->order_id} was cancelled automatically. Reason: {$reason}",
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to send cancellation notifications for order {$order->order_id}: " . $e->getMessage());
                    }
                });
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to cancel order ID {$order->order_id}: " . $e->getMessage());
            }
        }

        if ($count > 0) $this->info("Successfully cancelled {$count} orders. Reason: {$reason}");
    }
}
