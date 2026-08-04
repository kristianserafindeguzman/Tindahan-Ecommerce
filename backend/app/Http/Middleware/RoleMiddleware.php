<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more roles as parameters.
     * Usage in routes: middleware('role:Admin') or middleware('role:Admin,Vendor')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden. You do not have the required role to access this resource.',
            ], 403);
        }

        // Block suspended/inactive/deleted accounts even if they hold a valid token
        if (!$user->isActive()) {
            return response()->json([
                'message' => 'Your account is not active. Please contact support.',
                'error_code' => 'ACCOUNT_' . strtoupper($user->account_status),
            ], 403);
        }

        // For Vendor routes, also verify store approval
        if (in_array('Vendor', $roles)) {
            $store = $user->store?->load('approvalStatus');
            $vendorStatus = $store?->approvalStatus?->status ?? 'pending';
            if ($vendorStatus !== 'approved') {
                return response()->json([
                    'message' => 'Vendor store is not approved.',
                    'error_code' => 'VENDOR_NOT_APPROVED',
                ], 403);
            }
        }

        return $next($request);
    }
}
