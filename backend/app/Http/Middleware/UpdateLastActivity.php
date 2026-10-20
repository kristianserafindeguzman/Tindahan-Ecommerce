<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Update the authenticated user's last_activity_at timestamp
     * on every API request. This powers the "30 mins ago" display
     * in the Admin dashboard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $passive = $request->is('api/realtime/*', 'api/broadcasting/*')
            || ($request->isMethod('GET') && $request->header('X-Background-Sync') === '1');

        if ($request->user() && ! $passive) {
            $request->user()->update([
                'last_activity_at' => now(),
            ]);
        }

        return $response;
    }
}
