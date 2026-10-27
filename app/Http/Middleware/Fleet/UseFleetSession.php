<?php

namespace App\Http\Middleware\Fleet;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives the fleet subdomain its own session cookie name. Runs before StartSession, so a
 * campaign-site login can never be read as a fleet login (or the other way round), even
 * if SESSION_DOMAIN is later widened to cover every subdomain.
 */
class UseFleetSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.cookie' => config('fleet.session_cookie')]);

        return $next($request);
    }
}
