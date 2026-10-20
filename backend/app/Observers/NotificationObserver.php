<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\RealtimePublisher;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class NotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Notification $notification): void
    {
        RealtimePublisher::users([$notification->user_id], 'notifications');
    }
}
