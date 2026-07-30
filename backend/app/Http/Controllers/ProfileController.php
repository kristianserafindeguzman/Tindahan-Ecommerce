<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_picture')) {
            // Delete old photo if it exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profiles', 'public');
            
            // Bypass mass assignment or timestamps just in case
            User::where('user_id', $user->user_id)->update(['profile_picture' => $path]);
            
            return response()->json([
                'message' => 'Profile photo updated successfully',
                'profile_picture_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['message' => 'No photo provided'], 400);
    }

    public function updatePersonalInfo(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100'
        ]);

        $user = $request->user();
        User::where('user_id', $user->user_id)->update(['full_name' => $request->full_name]);

        return response()->json(['message' => 'Personal info updated successfully']);
    }

    public function requestPhoneOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15|unique:users,phone_number,' . $request->user()->user_id . ',user_id'
        ]);

        // Mock OTP generation based on AuthController::generateOtp()
        $otp = '123456'; 
        
        // In a real application, we would save this to the DB with an expiry, and send via SMS.
        // For now, we will store it in the session or cache. Since it's an API, cache is better.
        \Illuminate\Support\Facades\Cache::put('phone_otp_' . $request->user()->user_id, [
            'phone_number' => $request->phone_number,
            'otp' => $otp
        ], now()->addMinutes(10));

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyPhoneOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15',
            'code' => 'required|string|size:6'
        ]);

        $cacheKey = 'phone_otp_' . $request->user()->user_id;
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$cached || $cached['phone_number'] !== $request->phone_number || $cached['otp'] !== $request->code) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 400);
        }

        User::where('user_id', $request->user()->user_id)->update(['phone_number' => $request->phone_number]);
        
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        return response()->json(['message' => 'Phone number updated successfully']);
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100|unique:users,email,' . $request->user()->user_id . ',user_id'
        ]);

        User::where('user_id', $request->user()->user_id)->update(['email' => $request->email]);

        return response()->json(['message' => 'Email updated successfully']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = collect(\Illuminate\Support\Facades\DB::select('SELECT password_hash FROM users WHERE user_id = ?', [$request->user()->user_id]))->first();

        if (!Hash::check($request->current_password, $user->password_hash)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        User::where('user_id', $request->user()->user_id)->update(['password_hash' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function updateStoreHours(Request $request)
    {
        $request->validate([
            'operatingDays' => 'required|array',
        ]);

        $days = $request->operatingDays;
        $activeDays = [];
        $openingTime = null;
        $closingTime = null;

        foreach ($days as $day) {
            if (isset($day['isOpen']) && $day['isOpen']) {
                $activeDays[] = substr($day['name'], 0, 3); // e.g. "Mon"
                // Pick the first valid time found as the global store time
                if (!$openingTime && isset($day['openTime'])) {
                    $openingTime = $day['openTime'] . ':00'; // Append seconds
                }
                if (!$closingTime && isset($day['closeTime'])) {
                    $closingTime = $day['closeTime'] . ':00';
                }
            }
        }

        $user = $request->user();
        if ($user->role === 'Vendor' && $user->store) {
            $user->store->update([
                'operating_days' => $activeDays,
                'opening_time' => $openingTime,
                'closing_time' => $closingTime,
            ]);
        }

        return response()->json(['message' => 'Store hours updated successfully']);
    }

    public function deleteAccount(Request $request)
    {
        $user = clone $request->user();
        
        // Revoke tokens
        $user->tokens()->delete();
        
        // Soft delete user
        User::where('user_id', $user->user_id)->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
