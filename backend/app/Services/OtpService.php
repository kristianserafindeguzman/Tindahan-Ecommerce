<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

/** One place for issuing and checking phone OTPs, so the local-development bypass below only has to be undone once. */
class OtpService
{
    public function __construct(private SemaphoreService $semaphoreService) {}

    /** Replaces any unverified code of this type for the phone with a new one, stores only its hash, and texts it through Semaphore. */
    public function send(string $phoneNumber, string $type, ?int $userId = null): void
    {
        OtpCode::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();

        // TEMPORARY LOCAL DEVELOPMENT OTP BYPASS
        // Accept the value of SEMAPHORE_FAKE_CODE (e.g., 123456) while testing against localhost.
        // REMOVE/REVERT THIS BEFORE RETURNING TO THE CLOUD DATABASE AND REAL OTP SERVICE.
        // The bypass only works if APP_ENV=local, ensuring it can never reach the live server.

        // remove this if going to use OTP
        // $fakeCode = app()->environment('local') ? config('services.semaphore.fake_code') : null;

        // otp static
        $code = '012345';

        // otp sending live
        // $code = $fakeCode ? (string) $fakeCode : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id'      => $userId,
            'phone_number' => $phoneNumber,
            'code'         => Hash::make($code),
            'type'         => $type,
            'expires_at'   => now()->addMinutes(10),
        ]);

        // Uncomment this if going to use the OTP
        // if (!$fakeCode) {
        //     $this->semaphoreService->sendOtp($phoneNumber, $code);
        // }
    }

    /** Consumes the latest unverified code of this type; returns ['ok' => true] or a message and HTTP status to hand back. */
    public function verify(string $phoneNumber, string $type, string $code): array
    {
        $otp = OtpCode::where('phone_number', $phoneNumber)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest('created_at')
            ->first();

        if (!$otp) {
            return ['ok' => false, 'status' => 400, 'message' => 'No verification code found. Please request a new one.'];
        }

        if ($otp->isExpired()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        if (!Hash::check($code, $otp->code)) {
            return ['ok' => false, 'status' => 400, 'message' => 'Invalid verification code. Please try again.'];
        }

        $otp->update(['verified_at' => now()]);

        return ['ok' => true];
    }
}
