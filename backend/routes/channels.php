<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::connection('reverb')->channel('users.{userId}', function (User $user, string $userId) {
    return (string) $user->user_id === $userId && $user->isActive()
        && (in_array($user->role, ['Consumer', 'Admin'])
            || ($user->role === 'Vendor' && $user->store?->approvalStatus?->status === 'approved'));
}, ['guards' => ['sanctum']]);

Broadcast::connection('reverb')->channel('vendor-applications.{userId}', function (User $user, string $userId) {
    return (string) $user->user_id === $userId && $user->isActive()
        && $user->role === 'Vendor' && $user->store !== null;
}, ['guards' => ['sanctum']]);
