<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Str;

// These small invalidation signals must not wait behind queued mail or worker polling.
// Dispatch remains deferred until commit; delivery failures are rescued and reported.
class RealtimeChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    public string $eventId;

    public function __construct(
        public array $userIds,
        public string $topic,
        public bool $applicationOnly = false,
    ) {
        $this->eventId = (string) Str::uuid();
    }

    public function broadcastConnections(): array
    {
        // Select this connection explicitly without changing BROADCAST_CONNECTION.
        return ['reverb'];
    }

    public function broadcastOn(): array
    {
        $prefix = $this->applicationOnly ? 'vendor-applications' : 'users';

        return array_map(fn ($id) => new PrivateChannel("{$prefix}.{$id}"), $this->userIds);
    }

    public function broadcastAs(): string
    {
        return 'realtime.changed';
    }

    public function broadcastWith(): array
    {
        // No order details, notification text, account data or credentials on sockets.
        // Every actual read must pass current API authentication and authorization.
        return ['event_id' => $this->eventId, 'topic' => $this->topic];
    }
}
