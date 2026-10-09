<?php

namespace App\Http\Middleware;

use App\Support\Visitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page view for each successful public page load, including Inertia
 * navigations (each one is a request to the server). Written after the response is sent.
 */
class TrackPageView
{
    private const EXCLUDED = ['admin', 'admin/*', 'login', 't/*', 'up', 'api/*', 'storage/*', 'build/*', 'newsletter/confirm/*', 'newsletter/unsubscribe/*', 'materials/*/download'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!$this->isPageView($request, $response) || !Visitor::shouldTrack($request)) {
            return;
        }

        try {
            DB::table('page_views')->insert([
                'visitor_id' => Visitor::id($request),
                'path' => substr('/'.ltrim($request->path(), '/'), 0, 255),
                'referrer_host' => $this->externalReferrer($request),
                'utm_source' => $this->utm($request, 'utm_source'),
                'utm_medium' => $this->utm($request, 'utm_medium'),
                'utm_campaign' => $this->utm($request, 'utm_campaign'),
                'device' => Visitor::device($request),
                'browser' => Visitor::browser($request),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Analytics must never break the site
            report($e);
        }
    }

    private function isPageView(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && !$request->is(...self::EXCLUDED)
            // Inertia link prefetches and partial reloads aren't new page views
            && $request->header('Purpose') !== 'prefetch'
            && !$request->hasHeader('X-Inertia-Partial-Data')
            && ($request->hasHeader('X-Inertia') || str_contains((string) $response->headers->get('Content-Type'), 'text/html'));
    }

    // Only the first page of a visit has an outside referrer; in-site navigation is ignored
    private function externalReferrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (!$host || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return substr(preg_replace('/^(www\.|m\.|l\.|lm\.)/i', '', strtolower($host)), 0, 255);
    }

    private function utm(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key));

        return $value === '' ? null : substr(strtolower($value), 0, 100);
    }
}
