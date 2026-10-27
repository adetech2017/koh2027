<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $guard = auth('fleet_admin');

        if (!$guard->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The provided credentials do not match our records.']);
        }

        $admin = $guard->user();

        // Suspended accounts keep their password but can't sign in
        if (!$admin->isActive()) {
            $guard->logout();
            throw ValidationException::withMessages(['email' => 'This account has been suspended. Contact a super admin.']);
        }

        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->saveQuietly();
        AuditLog::record('admin.login', $admin, actor: $admin);

        return response()->json(['admin' => $admin->toApi()]);
    }

    public function me(): JsonResponse
    {
        return response()->json(['admin' => auth('fleet_admin')->user()->toApi()]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('admin.logout');
        auth('fleet_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }
}
