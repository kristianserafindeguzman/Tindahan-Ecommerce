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

        if ($request->user()) {
            $request->user()->update([
                'last_activity_at' => now(),
            ]);
        }

        return $response;
    }
}
