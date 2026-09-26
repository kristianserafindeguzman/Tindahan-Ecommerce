<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** A lapsed cart row keeps its share of inventory.reserved_quantity until something releases it; this is the one place that does. */
class CartReservationService
{
    /** Releases lapsed holds on one product; the caller must already hold its lock, and the instance is saved in place. */
    public function releaseExpiredFor(Inventory $inventory): int
    {
        $expired = CartItem::where('inventory_id', $inventory->inventory_id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->lockForUpdate()
            ->get();

        if ($expired->isEmpty()) {
            return 0;
        }

        $released = (int) $expired->sum('reserved_quantity');

        $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $released);
        $inventory->save();

        $this->deleteAndNotify($expired, $inventory->product_name);

        return $released;
    }

    /** The scheduled sweep: one transaction per product, so a bad row cannot strand the rest. */
    public function releaseAllExpired(): int
    {
        $inventoryIds = CartItem::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->distinct()
            ->pluck('inventory_id');

        $released = 0;

        foreach ($inventoryIds as $inventoryId) {
            try {
                $released += DB::transaction(function () use ($inventoryId) {
                    $inventory = Inventory::where('inventory_id', $inventoryId)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        // The product is gone; cart rows cascade with it, so there is nothing to release.
                        return 0;
                    }

                    return $this->releaseExpiredFor($inventory);
                });
            } catch (\Exception $e) {
                Log::error("Failed to release expired reservations for inventory {$inventoryId}: " . $e->getMessage());
            }
        }

        return $released;
    }

    /** Deletes the rows and tells each consumer why their item vanished; a failed notice never undoes the release. */
    private function deleteAndNotify($expired, ?string $productName): void
    {
        $productName = $productName ?: 'a product';
        $consumerIds = $expired->pluck('consumer_id')->unique();

        CartItem::whereIn('cart_id', $expired->pluck('cart_id'))->delete();

        foreach ($consumerIds as $consumerId) {
            try {
                Notification::create([
                    'user_id' => $consumerId,
                    'title' => 'Cart Item Expired',
                    'message' => "Your reservation for '{$productName}' has expired and it was removed from your cart.",
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to notify consumer about cart expiration: ' . $e->getMessage());
            }
        }
    }
}
