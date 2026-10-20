<?php

namespace App\Services;

use App\Events\RealtimeChanged;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;

class RealtimePublisher
{
    public static function users(array $ids, string $topic, bool $applicationOnly = false): void
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (! $ids || ! config('realtime.enabled') || ! config('app.key')) {
            return;
        }

        // Delivery failures must never fail an already committed action.
        rescue(fn () => event(new RealtimeChanged($ids, $topic, $applicationOnly)));
    }

    public static function order(Order $order): void
    {
        rescue(function () use ($order) {
            $ownerId = Store::whereKey($order->store_id)->value('owner_id');
            self::users([$order->consumer_id, $ownerId], 'orders');
        });
    }

    public static function application(int $storeId): void
    {
        rescue(function () use ($storeId) {
            $admins = User::where('role', 'Admin')->where('account_status', 'active')->pluck('user_id')->all();
            self::users($admins, 'applications');
            $ownerId = Store::whereKey($storeId)->value('owner_id');
            self::users([$ownerId], 'profile', true);
        });
    }
}
