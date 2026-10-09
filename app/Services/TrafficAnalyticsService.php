<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reports over the first-party page_views / site_events tables.
 *
 * Visitor IDs rotate daily, so "visitors" over a range is the sum of each day's
 * unique visitors: someone who comes back on three days counts three times.
 */
class TrafficAnalyticsService
{
    // Friendly names for the site's public paths
    private const PAGE_NAMES = [
        '/' => 'Home',
        '/about' => 'About',
        '/platforms' => 'Platforms',
        '/contact' => 'Contact',
        '/privacy' => 'Privacy',
        '/gallery' => 'Gallery',
        '/events' => 'Events',
        '/news' => 'News',
        '/materials' => 'Manifesto',
        '/merchandise' => 'Merchandise',
    ];

    public function summary(int $days): array
    {
        [$start, $previousStart] = $this->bounds($days);

        $current = $this->totals($start, now());
        $previous = $this->totals($previousStart, $start);

        return [
            ...$current,
            'previous' => $previous,
            // Anyone with a page view in the last 5 minutes
            'liveNow' => DB::table('page_views')->where('created_at', '>=', now()->subMinutes(5))->distinct()->count('visitor_id'),
        ];
    }

    public function daily(int $days): array
    {
        [$start] = $this->bounds($days);

        $rows = DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(0, $days - 1))->map(function ($i) use ($start, $rows) {
            $date = $start->copy()->addDays($i)->toDateString();

            return [
                'date' => $date,
                'visitors' => (int) ($rows[$date]->visitors ?? 0),
                'views' => (int) ($rows[$date]->views ?? 0),
            ];
        })->all();
    }

    public function topPages(int $days, int $limit = 10): array
    {
        [$start] = $this->bounds($days);

        return DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT visitor_id, DATE(created_at)) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'name' => $this->pageName($row->path),
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    /**
     * Where visits came from: a campaign tag (utm_source) if present, otherwise the referring site.
     */
    public function sources(int $days, int $limit = 10): array
    {
        [$start] = $this->bounds($days);

        return DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->where(fn ($q) => $q->whereNotNull('utm_source')->orWhereNotNull('referrer_host'))
            ->selectRaw('COALESCE(utm_source, referrer_host) as source, MAX(utm_source IS NOT NULL) as tagged, COUNT(DISTINCT visitor_id, DATE(created_at)) as visitors')
            ->groupBy('source')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source,
                'label' => $this->sourceName($row->source),
                'tagged' => (bool) $row->tagged,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    public function campaigns(int $days, int $limit = 10): array
    {
        [$start] = $this->bounds($days);

        return DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign as campaign, COUNT(DISTINCT visitor_id, DATE(created_at)) as visitors')
            ->groupBy('utm_campaign')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['campaign' => $row->campaign, 'visitors' => (int) $row->visitors])
            ->all();
    }

    public function devices(int $days): array
    {
        [$start] = $this->bounds($days);

        $counts = DB::table('page_views')
            ->where('created_at', '>=', $start)
            ->selectRaw('device, COUNT(DISTINCT visitor_id, DATE(created_at)) as visitors')
            ->groupBy('device')
            ->pluck('visitors', 'device');

        return collect(['mobile', 'desktop', 'tablet'])
            ->map(fn ($device) => ['device' => $device, 'visitors' => (int) ($counts[$device] ?? 0)])
            ->all();
    }

    /**
     * Click events grouped by type, plus the most-clicked individual items.
     */
    public function clicks(int $days, int $limit = 10): array
    {
        [$start, $previousStart] = $this->bounds($days);

        $byName = fn ($from, $to) => DB::table('site_events')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('name, COUNT(*) as clicks')
            ->groupBy('name')
            ->pluck('clicks', 'name');

        $current = $byName($start, now());
        $previous = $byName($previousStart, $start);

        return [
            'byType' => collect(\App\Http\Controllers\TrackEventController::NAMES)
                ->map(fn ($name) => [
                    'name' => $name,
                    'clicks' => (int) ($current[$name] ?? 0),
                    'previous' => (int) ($previous[$name] ?? 0),
                ])
                ->all(),
            'top' => DB::table('site_events')
                ->where('created_at', '>=', $start)
                ->selectRaw('name, label, COUNT(*) as clicks, COUNT(DISTINCT visitor_id, DATE(created_at)) as visitors')
                ->groupBy('name', 'label')
                ->orderByDesc('clicks')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'label' => $row->label, 'clicks' => (int) $row->clicks, 'visitors' => (int) $row->visitors])
                ->all(),
        ];
    }

    private function totals(Carbon $from, Carbon $to): array
    {
        // One row per visitor per day, with how many pages they viewed that day
        $visits = DB::table('page_views')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('visitor_id, DATE(created_at) as day, COUNT(*) as views')
            ->groupBy('visitor_id', 'day');

        $row = DB::query()->fromSub($visits, 'v')
            ->selectRaw('COUNT(*) as visitors, COALESCE(SUM(views), 0) as views, COALESCE(SUM(views = 1), 0) as single_page')
            ->first();

        $visitors = (int) $row->visitors;

        return [
            'visitors' => $visitors,
            'views' => (int) $row->views,
            'pagesPerVisit' => $visitors ? round($row->views / $visitors, 1) : 0,
            // Share of visits that viewed only one page
            'bounceRate' => $visitors ? round(($row->single_page / $visitors) * 100) : 0,
        ];
    }

    private function bounds(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        return [$start, $start->copy()->subDays($days)];
    }

    private function pageName(string $path): string
    {
        if (isset(self::PAGE_NAMES[$path])) {
            return self::PAGE_NAMES[$path];
        }

        foreach (['/news/' => 'News article', '/events/' => 'Event', '/merchandise/' => 'Product'] as $prefix => $label) {
            if (str_starts_with($path, $prefix)) {
                return $label.': '.str(substr($path, strlen($prefix)))->replace('-', ' ')->title();
            }
        }

        return $path;
    }

    private function sourceName(string $source): string
    {
        // utm_source values from the admin's link builder
        $tagged = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'x' => 'X (Twitter)', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'sms' => 'SMS', 'email' => 'Email', 'flyer' => 'Flyer / QR code'];
        if (isset($tagged[$source])) {
            return $tagged[$source];
        }

        return match (true) {
            (bool) preg_match('/(^|\.)google\./', $source) => 'Google',
            (bool) preg_match('/(^|\.)bing\.com$/', $source) => 'Bing',
            (bool) preg_match('/(^|\.)(facebook\.com|fb\.com|fb\.me)$/', $source) => 'Facebook',
            (bool) preg_match('/(^|\.)instagram\.com$/', $source) => 'Instagram',
            (bool) preg_match('/(^|\.)(t\.co|twitter\.com|x\.com)$/', $source) => 'X (Twitter)',
            (bool) preg_match('/(^|\.)linkedin\.com$/', $source) => 'LinkedIn',
            (bool) preg_match('/(^|\.)(whatsapp\.com|wa\.me)$/', $source) => 'WhatsApp',
            (bool) preg_match('/(^|\.)youtube\.com$/', $source) => 'YouTube',
            (bool) preg_match('/(^|\.)tiktok\.com$/', $source) => 'TikTok',
            default => $source,
        };
    }
}
