<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\RealtimePublisher;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class OrderObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Order $order): void
    {
        RealtimePublisher::order($order);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(['status', 'scheduled_pickup_at', 'ready_for_pickup_at', 'cancellation_reason'])) {
            RealtimePublisher::order($order);
        }
    }
}
