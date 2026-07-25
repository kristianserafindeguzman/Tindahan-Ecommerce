<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStatus;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Dashboard summary statistics.
     *
     * GET /api/admin/stats
     */
    public function stats()
    {
        return response()->json([
            'total_vendors'     => User::where('role', 'Vendor')->count(),
            'pending_approvals' => ApprovalStatus::where('status', 'pending')->count(),
            'total_consumers'   => User::where('role', 'Consumer')
                ->where('account_status', '!=', 'deleted')
                ->count(),
            'total_users'       => User::where('account_status', '!=', 'deleted')->count(),
        ]);
    }

    /**
     * List vendors with pending approval status.
     *
     * GET /api/admin/vendors/pending
     */
    public function pendingVendors(Request $request)
    {
        $query = ApprovalStatus::where('status', 'pending')
            ->with(['store.owner']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('store.owner', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $pending = $query->get()->map(function ($approval) {
            $store = $approval->store;
            $owner = $store?->owner;

            return [
                'approval_id' => $approval->approval_id,
                'store_id'    => $approval->store_id,
                'store_name'  => $store?->store_name,
                'owner_name'  => $owner?->full_name,
                'email'       => $owner?->email,
                'phone'       => $owner?->phone_number,
                'applied_at'  => $owner?->created_at,
            ];
        });

        return response()->json($pending);
    }

    /**
     * Approve a vendor's store application.
     *
     * POST /api/admin/vendors/{storeId}/approve
     */
    public function approveVendor(Request $request, $storeId)
    {
        $approval = ApprovalStatus::where('store_id', $storeId)->firstOrFail();
        $adminId = $request->user()->user_id;

        $approval->update([
            'status'      => 'approved',
            'admin_id'    => $adminId,
            'reviewed_at' => now(),
        ]);

        SystemAuditLog::create([
            'admin_id'         => $adminId,
            'action_performed' => "Approved vendor application for store ID {$storeId}",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => 'Vendor application approved successfully.',
        ]);
    }

    /**
     * Reject a vendor's store application.
     *
     * POST /api/admin/vendors/{storeId}/reject
     */
    public function rejectVendor(Request $request, $storeId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $approval = ApprovalStatus::where('store_id', $storeId)->firstOrFail();
        $adminId = $request->user()->user_id;

        $approval->update([
            'status'           => 'rejected',
            'admin_id'         => $adminId,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_at'      => now(),
        ]);

        SystemAuditLog::create([
            'admin_id'         => $adminId,
            'action_performed' => "Rejected vendor application for store ID {$storeId}. Reason: {$request->rejection_reason}",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => 'Vendor application rejected.',
        ]);
    }

    /**
     * List all vendors with store info, approval status, and quick insights.
     *
     * GET /api/admin/vendors
     */
    public function listVendors(Request $request)
    {
        $query = User::where('role', 'Vendor')
            ->with(['store.approvalStatus']);

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by account_status
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        $vendors = $query->get()->map(function ($vendor) {
            $store = $vendor->store;
            $approvalStatus = $store?->approvalStatus;

            // Quick insights: count completed orders and active products
            $completedOrders = 0;
            $activeProducts = 0;

            if ($store) {
                $completedOrders = \DB::table('orders')
                    ->where('store_id', $store->store_id)
                    ->where('status', 'completed')
                    ->count();

                $activeProducts = \DB::table('inventory')
                    ->where('store_id', $store->store_id)
                    ->where('status', 'active')
                    ->count();
            }

            return [
                'user_id'          => $vendor->user_id,
                'full_name'        => $vendor->full_name,
                'email'            => $vendor->email,
                'phone_number'     => $vendor->phone_number,
                'role'             => $vendor->role,
                'account_status'   => $vendor->account_status,
                'approval_status'  => $approvalStatus?->status ?? 'N/A',
                'last_activity_at' => $vendor->last_activity_at,
                'store_id'         => $store?->store_id,
                'store_name'       => $store?->store_name,
                'store_picture'    => $store?->store_picture,
                'opening_time'     => $store?->opening_time,
                'closing_time'     => $store?->closing_time,
                'latitude'         => $store?->latitude,
                'longitude'        => $store?->longitude,
                'completed_orders' => $completedOrders,
                'active_products'  => $activeProducts,
            ];
        });

        return response()->json($vendors);
    }

    /**
     * Update a vendor's account status (active/inactive/suspended).
     *
     * PATCH /api/admin/vendors/{userId}/status
     */
    public function updateVendorStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status'     => 'required|in:active,inactive,suspended',
            'suspension_message' => 'nullable|string|max:1000',
        ]);

        $vendor = User::where('user_id', $userId)
            ->where('role', 'Vendor')
            ->firstOrFail();

        $admin = $request->user();
        
        $suspensionMessage = null;
        if ($request->account_status === 'suspended') {
            $reason = $request->suspension_message ?? 'Violation of terms';
            $suspensionMessage = "Suspension notice from Admin: {$admin->full_name}. Reason: {$reason}";
        }

        $vendor->update([
            'account_status'     => $request->account_status,
            'suspension_message' => $suspensionMessage,
        ]);

        SystemAuditLog::create([
            'admin_id'         => $admin->user_id,
            'action_performed' => "Updated vendor {$userId} status to '{$request->account_status}'",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => "Vendor status updated to '{$request->account_status}'.",
        ]);
    }

    /**
     * List all consumers (never expose passwords).
     *
     * GET /api/admin/consumers
     */
    public function listConsumers(Request $request)
    {
        $query = User::where('role', 'Consumer')
            ->where('account_status', '!=', 'deleted');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $consumers = $query->get(['user_id', 'full_name', 'email', 'phone_number',
                                   'account_status', 'last_activity_at', 'created_at']);

        return response()->json($consumers);
    }

    /**
     * Update a consumer's account status (active/inactive/suspended).
     *
     * PATCH /api/admin/consumers/{userId}/status
     */
    public function updateConsumerStatus(Request $request, $userId)
    {
        $request->validate([
            'account_status'     => 'required|in:active,inactive,suspended',
            'suspension_message' => 'nullable|string|max:1000',
        ]);

        $consumer = User::where('user_id', $userId)
            ->where('role', 'Consumer')
            ->firstOrFail();

        $admin = $request->user();
        
        $suspensionMessage = null;
        if ($request->account_status === 'suspended') {
            $reason = $request->suspension_message ?? 'Violation of terms';
            $suspensionMessage = "Suspension notice from Admin: {$admin->full_name}. Reason: {$reason}";
        }

        $consumer->update([
            'account_status'     => $request->account_status,
            'suspension_message' => $suspensionMessage,
        ]);

        SystemAuditLog::create([
            'admin_id'         => $admin->user_id,
            'action_performed' => "Updated consumer {$userId} status to '{$request->account_status}'",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => "Consumer status updated to '{$request->account_status}'.",
        ]);
    }

    /**
     * Soft-delete a consumer (set account_status to 'deleted').
     * Preserves order history for vendor accounting/quick insights.
     *
     * DELETE /api/admin/consumers/{userId}
     */
    public function deleteConsumer(Request $request, $userId)
    {
        $consumer = User::where('user_id', $userId)
            ->where('role', 'Consumer')
            ->firstOrFail();

        // Soft delete — preserve the record and all related orders
        $consumer->update([
            'account_status' => 'deleted',
        ]);

        // Revoke all tokens so they can't access the API anymore
        $consumer->tokens()->delete();

        SystemAuditLog::create([
            'admin_id'         => $request->user()->user_id,
            'action_performed' => "Deactivated consumer account {$userId}",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => 'Consumer account has been deactivated.',
        ]);
    }
}
