<?php

namespace App\Http\Controllers\Fleet\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Services\Fleet\OtpService;
use App\Support\Fleet\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function requestCode(Request $request, OtpService $otp): JsonResponse
    {
        $phone = $this->phone($request);

        // The same reply whether or not the number is registered, so the form can't be used
        // to find out who has a campaign vehicle
        $beneficiary = Beneficiary::where('phone', $phone)->first();

        if ($beneficiary?->isActive()) {
            try {
                $otp->issue($phone, $request->ip());
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => "We couldn't send the code just now. Please try again in a few minutes."], 503);
            }
        }

        return response()->json([
            'message' => 'If this number is registered, a login code has been sent to it on WhatsApp.',
            'phone' => PhoneNumber::mask($phone),
        ]);
    }

    public function verify(Request $request, OtpService $otp): JsonResponse
    {
        $phone = $this->phone($request);
        $request->validate(['code' => ['required', 'digits:'.config('fleet.otp.length')]]);

        $beneficiary = Beneficiary::where('phone', $phone)->first();

        if (!$beneficiary?->isActive() || !$otp->verify($phone, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is wrong or has expired.']);
        }

        auth('fleet_beneficiary')->login($beneficiary);
        $request->session()->regenerate();
        $beneficiary->forceFill(['last_login_at' => now()])->saveQuietly();
        AuditLog::record('beneficiary.login', $beneficiary, actor: $beneficiary);

        return response()->json(['beneficiary' => $beneficiary->toApi()]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['beneficiary' => auth('fleet_beneficiary')->user()->toApi()]);
    }

    public function logout(Request $request): JsonResponse
    {
        auth('fleet_beneficiary')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    private function phone(Request $request): string
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        return PhoneNumber::normalize($request->input('phone'))
            ?? throw ValidationException::withMessages(['phone' => 'Enter a valid Nigerian mobile number, e.g. 0803 123 4567.']);
    }
}
