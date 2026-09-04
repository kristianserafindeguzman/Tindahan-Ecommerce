<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SemaphoreService
{
    /**
     * Send a one-time password through Semaphore.
     */
    public function sendOtp(string $phoneNumber, string $code): void
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        $response = Http::asForm()->post(
            'https://api.semaphore.co/api/v4/messages',
            [
                'apikey' => config('services.semaphore.api_key'),
                'number' => $phoneNumber,
                'message' => 'Your Tindahan verification code is {otp}. It expires in 10 minutes.',
                'sendername' => config('services.semaphore.sender_name'),
                'code' => $code,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Failed to send OTP through Semaphore.'
            );
        }
    }

    /**
     * Convert Philippine local format (09XXXXXXXXX)
     * to international format (639XXXXXXXXX).
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (str_starts_with($phoneNumber, '0')) {
            return '63' . substr($phoneNumber, 1);
        }

        if (str_starts_with($phoneNumber, '63')) {
            return $phoneNumber;
        }

        return $phoneNumber;
    }
}
