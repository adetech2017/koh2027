<?php

namespace App\Services\Fleet;

use App\Models\Fleet\Otp;
use Illuminate\Support\Facades\DB;

/**
 * WhatsApp login codes for beneficiaries. Only an HMAC of each code is stored, a new code
 * replaces any earlier one for the same number, and a code dies after a few wrong guesses.
 */
class OtpService
{
    public function __construct(private WhatsAppClient $whatsApp) {}

    /**
     * Create a code for the number and send it over WhatsApp.
     */
    public function issue(string $phone, ?string $ip = null): void
    {
        $length = config('fleet.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($phone, $code, $ip) {
            Otp::where('phone', $phone)->whereNull('consumed_at')->update(['consumed_at' => now()]);

            Otp::create([
                'phone' => $phone,
                'code_hash' => $this->hash($code),
                'expires_at' => now()->addMinutes(config('fleet.otp.ttl_minutes')),
                'ip' => $ip,
            ]);
        });

        $this->whatsApp->sendOtp($phone, $code);
    }

    /**
     * True if the code matches the number's current code; the code can't be used again either way
     * once it matches or runs out of attempts.
     */
    public function verify(string $phone, string $code): bool
    {
        return DB::transaction(function () use ($phone, $code) {
            $otp = Otp::where('phone', $phone)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                return false;
            }

            $otp->attempts++;

            $matches = hash_equals($otp->code_hash, $this->hash($code));

            if ($matches || $otp->attempts >= config('fleet.otp.max_attempts')) {
                $otp->consumed_at = now();
            }

            $otp->save();

            return $matches;
        });
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }
}
