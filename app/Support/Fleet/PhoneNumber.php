<?php

namespace App\Support\Fleet;

/**
 * Nigerian mobile numbers in the forms people actually type them (0803 123 4567,
 * 803-123-4567, 234803..., +234 803...) normalised to E.164 (+2348031234567).
 */
class PhoneNumber
{
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if (str_starts_with($digits, '234')) {
            $digits = substr($digits, 3);
        }

        // The trunk 0, including the "+234 (0) 803..." form
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Nigerian mobile numbers are 10 digits after the country code, starting 7, 8 or 9
        if (!preg_match('/^[789]\d{9}$/', $digits)) {
            return null;
        }

        return '+234'.$digits;
    }

    /**
     * +2348031234567 → +234 803 *** 4567, for messages that shouldn't show the full number.
     */
    public static function mask(string $e164): string
    {
        return substr($e164, 0, 4).' '.substr($e164, 4, 3).' *** '.substr($e164, -4);
    }
}
