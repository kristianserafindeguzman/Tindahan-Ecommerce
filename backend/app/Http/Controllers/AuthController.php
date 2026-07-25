<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStatus;
use App\Models\OtpCode;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new Consumer user.
     *
     * POST /api/register/consumer
     *
     * After creation, generates a mock OTP code and returns it.
     * In production, this code would be sent via SMS gateway.
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

        // Generate OTP for registration verification
        $this->generateOtp($user, 'registration');

        return response()->json([
            'message'      => 'Consumer registered successfully. Please verify your phone number.',
            'user'         => $user,
            'otp_sent'     => true,
            'phone_number' => $validated['phone_number'],
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
     * Verify OTP code (for registration or password reset).
     *
     * POST /api/otp/verify
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'code'         => 'required|string|size:6',
            'type'         => 'required|in:registration,password_reset',
        ]);

        $otp = OtpCode::where('phone_number', $request->phone_number)
            ->where('type', $request->type)
            ->whereNull('verified_at')
            ->latest('created_at')
            ->first();

        if (!$otp || !Hash::check($request->code, $otp->code)) {
            return response()->json([
                'message' => 'Invalid verification code. Please try again.',
            ], 422);
        }

        if ($otp->isExpired()) {
            return response()->json([
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        // Mark as verified
        $otp->update(['verified_at' => now()]);

        $responseData = [
            'message'  => 'Verification successful.',
            'verified' => true,
        ];

        // For password reset, return a temporary reset token
        if ($request->type === 'password_reset') {
            $resetToken = Str::random(64);

            // Store the reset token on the user for validation during password reset
            $user = User::where('phone_number', $request->phone_number)->first();
            if ($user) {
                // Use the password_reset_tokens table
                \DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($resetToken), 'created_at' => now()]
                );
            }

            $responseData['reset_token'] = $resetToken;
        }

        return response()->json($responseData);
    }

    /**
     * Resend OTP code.
     *
     * POST /api/otp/resend
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'type'         => 'required|in:registration,password_reset',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Phone number not found.',
            ], 404);
        }

        $this->generateOtp($user, $request->type);

        return response()->json([
            'message'  => 'A new verification code has been sent.',
            'otp_sent' => true,
        ]);
    }

    /**
     * Initiate forgot password flow.
     * Only Consumers and Vendors can reset — Admins are blocked.
     *
     * POST /api/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this phone number.',
            ], 404);
        }

        // Block Admin password resets via SMS
        if ($user->role === 'Admin') {
            return response()->json([
                'message' => 'Admin accounts cannot be reset via SMS. Please contact DTI Negosyo Center.',
            ], 403);
        }

        // Block deleted/suspended accounts
        if (in_array($user->account_status, ['deleted', 'suspended'])) {
            return response()->json([
                'message' => 'This account is no longer active. Please contact support.',
            ], 403);
        }

        $this->generateOtp($user, 'password_reset');

        return response()->json([
            'message'  => 'A verification code has been sent to your phone number.',
            'otp_sent' => true,
        ]);
    }

    /**
     * Reset password after OTP verification.
     *
     * POST /api/forgot-password/reset
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'reset_token'  => 'required|string',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Verify the reset token
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->reset_token, $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 422);
        }

        // Update the password
        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        // Clean up the reset token
        \DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now log in.',
        ]);
    }

    /**
     * Unified login for all roles.
     *
     * POST /api/login
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

        // Block suspended, deleted, or inactive accounts
        if (!$user->isActive()) {
            $statusMessages = [
                'suspended' => 'Your account has been suspended. Please contact support.',
                'deleted'   => 'This account has been deactivated.',
                'inactive'  => 'Your account is inactive. Please contact support.',
            ];

            return response()->json([
                'message' => $statusMessages[$user->account_status] ?? 'Account is not active.',
            ], 403);
        }

        // Revoke any existing tokens for security
        $user->tokens()->delete();

        // Update last activity
        $user->update(['last_activity_at' => now()]);

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

    /**
     * Generate a mock OTP code and store it.
     *
     * In production, replace the mock code with a random 6-digit
     * number and send it via an SMS gateway (Semaphore, Twilio, etc.).
     */
    private function generateOtp(User $user, string $type): OtpCode
    {
        // Invalidate any existing unverified OTPs of this type
        OtpCode::where('user_id', $user->user_id)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();

        // Mock code — always 123456 for development
        // In production: $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $code = '123456';

        return OtpCode::create([
            'user_id'      => $user->user_id,
            'phone_number' => $user->phone_number,
            'code'         => Hash::make($code),
            'type'         => $type,
            'expires_at'   => now()->addMinutes(10),
        ]);
    }
}
