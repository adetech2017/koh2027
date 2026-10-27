<?php

namespace App\Http\Middleware\Fleet;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a suspended fleet admin or beneficiary on their next request, even though their
 * session survived the suspension. Used as `fleet.active:fleet_admin` after `auth:fleet_admin`.
 */
class EnsureFleetAccountActive
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $account = auth($guard)->user();

        if ($account && !$account->isActive()) {
            auth($guard)->logout();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'This account has been suspended. Contact the fleet office.'], 403);
        }

        return $next($request);
    }
}
