<?php

namespace App\Observers;

use App\Models\ApprovalStatus;
use App\Services\RealtimePublisher;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ApprovalStatusObserver implements ShouldHandleEventsAfterCommit
{
    public function created(ApprovalStatus $approval): void
    {
        RealtimePublisher::application($approval->store_id);
    }

    public function updated(ApprovalStatus $approval): void
    {
        if ($approval->wasChanged(['status', 'rejection_reason'])) {
            RealtimePublisher::application($approval->store_id);
        }
    }
}
