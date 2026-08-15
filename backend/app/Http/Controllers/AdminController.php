<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStatus;
use App\Models\SystemAuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
     * Get pending and rejected vendor applications.
     *
     * GET /api/admin/vendors/pending
     */
    public function pendingVendors(Request $request)
    {
        $query = ApprovalStatus::with(['store.owner'])
            ->whereIn('status', ['pending', 'rejected']);

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
                'status'      => $approval->status,
                'applied_at'  => $owner?->created_at,
                'store'       => [
                    'store_name' => $store?->store_name,
                    'store_picture_url' => $store?->store_picture_url,
                    'operating_days' => $store?->operating_days,
                    'opening_time' => $store?->opening_time,
                    'closing_time' => $store?->closing_time,
                    'latitude' => $store?->latitude,
                    'longitude' => $store?->longitude,
                    'owner' => [
                        'full_name' => $owner?->full_name,
                    ]
                ]
            ];
        });

        return response()->json($pending);
    }

    /**
     * Export pending and rejected vendor applications as a PDF.
     *
     * GET /api/admin/vendors/pending/export
     */
    public function exportPendingVendors(Request $request)
    {
        $query = ApprovalStatus::with(['store.owner'])
            ->whereIn('status', ['pending', 'rejected']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('store.owner', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $approvals = $query->get();

        // Build report metadata from the currently authenticated Admin
        // so exported reports are traceable to the user who generated them.
        $admin = auth()->user();
        $date = Carbon::now('Asia/Manila')->format('F j, Y \a\t g:i A');

        $pdf = Pdf::loadView('pdf.admin-approvals-report', [
            'approvals' => $approvals,
            'admin'     => $admin,
            'date'      => $date
        ]);

        return $pdf->download('Tindahan_Admin_Approvals_Export_' . Carbon::now('Asia/Manila')->format('Y-m-d') . '.pdf');
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
        if ($request->tab === 'deleted') {
            $query = User::onlyTrashed()->where('role', 'Vendor');
        } else {
            $query = User::where('role', 'Vendor');
        }

        $query->whereHas('store', function ($q) use ($request) {
            if ($request->tab === 'deleted') {
                $q->withTrashed();
            }
            $q->whereHas('approvalStatus', function ($q2) {
                $q2->where('status', 'approved');
            });
        })->with(['store' => function ($q) use ($request) {
            if ($request->tab === 'deleted') {
                $q->withTrashed();
            }
            $q->withCount([
                'inventory as active_products_count' => function ($q2) {
                    $q2->where('status', 'active');
                },
                'orders as orders_count' => function ($q2) {
                    $q2->where('status', '!=', 'cancelled');
                },
            ])->with('approvalStatus');
        }]);

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
                'store_picture_url' => $store?->store_picture_url,
                'operating_days'   => $store?->operating_days,
                'opening_time'     => $store?->opening_time,
                'closing_time'     => $store?->closing_time,
                'latitude'         => $store?->latitude,
                'longitude'        => $store?->longitude,
                'active_products'  => $store?->active_products_count ?? 0,
                'orders_count'     => $store?->orders_count ?? 0,
            ];
        });

        return response()->json($vendors);
    }

    /**
     * Export registered vendors as a PDF.
     *
     * GET /api/admin/vendors/export
     */
    public function exportVendors(Request $request)
    {
        if ($request->tab === 'deleted') {
            $query = User::onlyTrashed()->where('role', 'Vendor');
        } else {
            $query = User::where('role', 'Vendor');
        }

        $query->whereHas('store', function ($q) use ($request) {
            if ($request->tab === 'deleted') {
                $q->withTrashed();
            }
            $q->whereHas('approvalStatus', function ($q2) {
                $q2->where('status', 'approved');
            });
        })->with(['store' => function ($q) use ($request) {
            if ($request->tab === 'deleted') {
                $q->withTrashed();
            }
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        $vendors = $query->get();

        // Build report metadata from the currently authenticated Admin
        // so exported reports are traceable to the user who generated them.
        $admin = auth()->user();
        $date = Carbon::now('Asia/Manila')->format('F j, Y \a\t g:i A');

        $pdf = Pdf::loadView('pdf.admin-vendors-report', [
            'vendors' => $vendors,
            'admin'   => $admin,
            'date'    => $date
        ]);

        return $pdf->download('Tindahan_Admin_Vendors_Export_' . Carbon::now('Asia/Manila')->format('Y-m-d') . '.pdf');
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
     * Soft-delete a vendor and their store.
     *
     * DELETE /api/admin/vendors/{userId}
     */
    public function deleteVendor(Request $request, $userId)
    {
        $vendor = User::where('user_id', $userId)
            ->where('role', 'Vendor')
            ->firstOrFail();

        $vendor->delete(); // Soft delete User

        if ($vendor->store) {
            $vendor->store->delete(); // Soft delete Store
        }

        $vendor->tokens()->delete();

        SystemAuditLog::create([
            'admin_id'         => $request->user()->user_id,
            'action_performed' => "Deactivated vendor account {$userId}",
            'created_at'       => now(),
        ]);

        return response()->json([
            'message' => 'Vendor account has been deactivated.',
        ]);
    }

    /**
     * List all consumers (never expose passwords).
     *
     * GET /api/admin/consumers
     */
    public function listConsumers(Request $request)
    {
        // Handle Active/Deleted Tab
        if ($request->tab === 'deleted') {
            $query = User::onlyTrashed()->where('role', 'Consumer');
        } else {
            $query = User::where('role', 'Consumer');
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $consumers = $query->get(['user_id', 'full_name', 'email', 'phone_number',
                                   'profile_picture', 'account_status', 'last_activity_at', 'created_at']);

        return response()->json($consumers);
    }

    /**
     * Export registered consumers as a PDF.
     *
     * GET /api/admin/consumers/export
     */
    public function exportConsumers(Request $request)
    {
        // Handle Active/Deleted Tab
        if ($request->tab === 'deleted') {
            $query = User::onlyTrashed()->where('role', 'Consumer');
        } else {
            $query = User::where('role', 'Consumer');
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $consumers = $query->get(['user_id', 'full_name', 'email', 'phone_number',
                                   'account_status', 'created_at', 'deleted_at']);

        // Build report metadata from the currently authenticated Admin
        // so exported reports are traceable to the user who generated them.
        $admin = auth()->user();
        $date = Carbon::now('Asia/Manila')->format('F j, Y \a\t g:i A');

        $pdf = Pdf::loadView('pdf.admin-consumers-report', [
            'consumers' => $consumers,
            'admin'     => $admin,
            'date'      => $date
        ]);

        return $pdf->download('Tindahan_Admin_Consumers_Export_' . Carbon::now('Asia/Manila')->format('Y-m-d') . '.pdf');
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

        // Soft delete
        $consumer->delete();

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
