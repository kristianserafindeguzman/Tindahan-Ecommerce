<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new Consumer user.
     *
     * POST /api/register/consumer
     */
    public function registerConsumer(Request $request)
    {
        $validated = $request->validate([
            'full_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:100|unique:users,email',
            'phone_number' => 'required|string|max:15',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'role'          => 'Consumer',
            'full_name'     => $validated['full_name'],
            'email'         => $validated['email'],
            'phone_number'  => $validated['phone_number'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Consumer registered successfully.',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * Register a new Vendor user with store and pending approval.
     *
     * POST /api/register/vendor
     */
    public function registerVendor(Request $request)
    {
        $validated = $request->validate([
            'full_name'     => 'required|string|max:100',
            'email'         => 'required|email|max:100|unique:users,email',
            'phone_number'  => 'required|string|max:15',
            'password'      => 'required|string|min:8|confirmed',
            'store_name'    => 'required|string|max:150',
            'store_picture' => 'required|image|max:10240',
            'opening_time'  => 'required|date_format:H:i',
            'closing_time'  => 'required|date_format:H:i',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
        ]);

        // 1. Create the vendor user
        $user = User::create([
            'role'          => 'Vendor',
            'full_name'     => $validated['full_name'],
            'email'         => $validated['email'],
            'phone_number'  => $validated['phone_number'],
            'password_hash' => Hash::make($validated['password']),
        ]);

        // 2. Store the uploaded store photo
        $photoPath = $request->file('store_picture')
            ->store('stores', 'public');

        // 3. Create the store record
        $store = Store::create([
            'owner_id'      => $user->user_id,
            'store_name'    => $validated['store_name'],
            'store_picture'  => $photoPath,
            'opening_time'  => $validated['opening_time'],
            'closing_time'  => $validated['closing_time'],
            'latitude'      => $validated['latitude'],
            'longitude'     => $validated['longitude'],
        ]);

        // 4. Create a pending approval status (admin_id is NULL — no reviewer yet)
        ApprovalStatus::create([
            'store_id'         => $store->store_id,
            'admin_id'         => null,
            'status'           => 'pending',
            'rejection_reason' => null,
            'reviewed_at'      => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Vendor registered successfully. Your application is under review.',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * Unified login for all roles.
     *
     * POST /api/login
     *
     * Accepts email + password. Returns token, role, and vendor
     * approval status if applicable.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Find the user by email or phone number
        $user = User::where('email', $request->email)
            ->orWhere('phone_number', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke any existing tokens for security
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        $responseData = [
            'message' => 'Login successful.',
            'user'    => $user,
            'token'   => $token,
            'role'    => $user->role,
        ];

        // If the user is a Vendor, include their store approval status
        if ($user->role === 'Vendor') {
            $store = $user->store()->with('approvalStatus')->first();

            if ($store && $store->approvalStatus) {
                $responseData['vendor_status']    = $store->approvalStatus->status;
                $responseData['rejection_reason'] = $store->approvalStatus->rejection_reason;
            } else {
                // Vendor registered but store/approval not found (edge case)
                $responseData['vendor_status']    = 'pending';
                $responseData['rejection_reason'] = null;
            }
        }

        return response()->json($responseData);
    }

    /**
     * Logout — revoke the current access token.
     *
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     *
     * GET /api/user
     */
    public function user(Request $request)
    {
        $user = $request->user();

        $responseData = [
            'user' => $user,
            'role' => $user->role,
        ];

        if ($user->role === 'Vendor') {
            $store = $user->store()->with('approvalStatus')->first();

            if ($store && $store->approvalStatus) {
                $responseData['vendor_status']    = $store->approvalStatus->status;
                $responseData['rejection_reason'] = $store->approvalStatus->rejection_reason;
            }
        }

        return response()->json($responseData);
    }
}
