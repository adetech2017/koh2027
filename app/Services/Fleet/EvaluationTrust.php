<?php

namespace App\Services\Fleet;

use App\Models\Fleet\EvaluationPeriod;
use Illuminate\Support\Carbon;

/**
 * Decides how much a reviewer can trust that a video is new and was taken with the vehicle:
 *
 * - verified_live: recorded in the app with that session's one-time code on screen, with GPS
 * - gallery_ok:    uploaded from the phone, and the video's own metadata says it was recorded
 *                  in this window, with a location
 * - unverified:    anything else (metadata stripped, e.g. a video forwarded on WhatsApp; no GPS)
 *
 * Flags list the specific concerns for the reviewer either way.
 */
class EvaluationTrust
{
    /**
     * @param  array{latitude: float, longitude: float}|null  $device  GPS from the phone at upload
     * @param  array  $video  VideoTools::inspect() result
     * @return array{0: string, 1: array<int, string>}
     */
    public function assess(string $source, bool $challengeValid, ?array $device, array $video, EvaluationPeriod $period, ?int $mileage, ?int $previousMileage): array
    {
        $flags = [];
        $metadataLocation = $video['latitude'] !== null ? ['latitude' => $video['latitude'], 'longitude' => $video['longitude']] : null;
        $location = $device ?? $metadataLocation;

        if (!$location) {
            $flags[] = 'no_location';
        } elseif (!$this->inRegion($location)) {
            $flags[] = 'outside_region';
        }

        if ($device && $metadataLocation && $this->distanceKm($device, $metadataLocation) > config('fleet.evaluations.location_mismatch_km')) {
            $flags[] = 'location_mismatch';
        }

        /** @var Carbon|null $recordedAt */
        $recordedAt = $video['recorded_at'];
        if ($source === 'live') {
            if (!$challengeValid) {
                $flags[] = 'no_valid_code';
            }
        } elseif (!$recordedAt) {
            $flags[] = 'no_recording_date';
        } elseif ($recordedAt->greaterThan(now()->addHour())) {
            $flags[] = 'recorded_in_future';
        }

        if ($mileage !== null && $previousMileage !== null && $mileage < $previousMileage) {
            $flags[] = 'mileage_decreased';
        }

        $level = match (true) {
            $source === 'live' && $challengeValid && $device && !array_intersect($flags, ['outside_region']) => 'verified_live',
            $source === 'gallery' && $recordedAt && $recordedAt->greaterThanOrEqualTo($period->starts_at) && $location
                && !array_intersect($flags, ['recorded_in_future', 'location_mismatch', 'outside_region']) => 'gallery_ok',
            default => 'unverified',
        };

        return [$level, $flags];
    }

    private function inRegion(array $point): bool
    {
        $region = config('fleet.evaluations.region');

        return $point['latitude'] >= $region['min_lat'] && $point['latitude'] <= $region['max_lat']
            && $point['longitude'] >= $region['min_lng'] && $point['longitude'] <= $region['max_lng'];
    }

    private function distanceKm(array $a, array $b): float
    {
        $rad = M_PI / 180;
        $dLat = ($b['latitude'] - $a['latitude']) * $rad;
        $dLng = ($b['longitude'] - $a['longitude']) * $rad;
        $h = sin($dLat / 2) ** 2 + cos($a['latitude'] * $rad) * cos($b['latitude'] * $rad) * sin($dLng / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($h)));
    }
}
