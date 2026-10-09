<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Anonymous visitor fingerprinting for first-party analytics (no cookies, nothing stored on the device).
 */
class Visitor
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|headless|lighthouse|pingdom|uptime|monitor/i';

    /**
     * Same visitor + same day = same ID. Rotates daily, so visits can't be linked across days.
     */
    public static function id(Request $request): string
    {
        return substr(hash('sha256', implode('|', [
            now()->toDateString(),
            config('app.key'),
            $request->ip(),
            (string) $request->userAgent(),
        ])), 0, 16);
    }

    /**
     * Skip bots, signed-in staff, and visitors who ask not to be tracked.
     */
    public static function shouldTrack(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent !== ''
            && !preg_match(self::BOT_PATTERN, $agent)
            && !$request->user()
            && $request->header('DNT') !== '1'
            && $request->header('Sec-GPC') !== '1';
    }

    public static function device(Request $request): string
    {
        $agent = (string) $request->userAgent();

        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $agent) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Opera Mini|IEMobile/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }

    public static function browser(Request $request): ?string
    {
        $agent = (string) $request->userAgent();

        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => null,
        };
    }
}
