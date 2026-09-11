<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Indonesian mobile number in international format: 62 followed by 10-12 digits (12-14 digits total).
 */
class NomorHpIndonesia implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^62\d{10,12}$/', $value) !== 1) {
            $fail('Nomor HP harus diawali 62 dan terdiri dari 12-14 digit angka, contoh: 6281234567890.');
        }
    }

    /**
     * Normalise common Indonesian formats (08xx, +62xx, 8xx, with spaces/dashes) to 62xx.
     */
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
