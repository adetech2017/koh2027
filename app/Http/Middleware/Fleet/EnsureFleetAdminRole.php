<?php

namespace App\Http\Middleware\Fleet;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits a fleet admin route to the given roles, e.g. `fleet.role:super_admin,fleet_officer`.
 */
class EnsureFleetAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = auth('fleet_admin')->user();

        if (!$admin || ($roles && !in_array($admin->role, $roles))) {
            abort(403);
        }

        return $next($request);
    }
}
