<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class RealtimeController extends Controller
{
    public function authenticate(Request $request)
    {
        $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'regex:/^private-(users|vendor-applications)\.\d+$/'],
        ]);

        // The existing default broadcaster may be null/log. Authorize against the
        // same explicit connection used for delivery, without an environment switch.
        return response()->json(Broadcast::connection('reverb')->auth($request));
    }

    public function config(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['Consumer', 'Vendor', 'Admin']), 403);
        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Your account is not active.',
                'error_code' => 'ACCOUNT_'.strtoupper($user->account_status),
            ], 403);
        }

        $vendorStatus = $user->role === 'Vendor' ? $user->store?->approvalStatus?->status : null;
        $channels = [];
        if ($user->role !== 'Vendor' || $vendorStatus === 'approved') {
            $channels[] = 'users.'.$user->user_id;
        }
        if ($user->role === 'Vendor') {
            $channels[] = 'vendor-applications.'.$user->user_id;
        }

        $local = app()->environment('local', 'testing');
        $scheme = config('realtime.public_scheme') ?? ($local && ! $request->secure() ? 'http' : 'https');

        return response()->json([
            'enabled' => (bool) config('realtime.enabled') && (bool) config('app.key'),
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('realtime.public_host') ?? $request->getHost(),
            'port' => config('realtime.public_port') ?? ($scheme === 'https' ? 443 : (int) config('realtime.server_port', 8080)),
            'scheme' => $scheme,
            'path' => config('realtime.public_path'),
            'channels' => $channels,
            'user_id' => $user->user_id,
            'role' => $user->role,
            'vendor_status' => $vendorStatus,
        ])->header('Cache-Control', 'no-store');
    }
}
