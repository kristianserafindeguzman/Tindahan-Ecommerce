<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return; // The accompanying string rule handles invalid input types.
        }

        $checks = [
            [mb_strlen($value, 'UTF-8') >= 8, 'Password must be at least 8 characters.'],
            [preg_match('/\p{Lu}/u', $value) === 1, 'Password must contain at least one uppercase letter.'],
            [preg_match('/\p{Ll}/u', $value) === 1, 'Password must contain at least one lowercase letter.'],
            [preg_match('/[\p{P}\p{S}]/u', $value) === 1, 'Password must contain at least one symbol.'],
        ];

        foreach ($checks as [$passes, $message]) {
            if (!$passes) {
                $fail($message);
            }
        }
    }
}
