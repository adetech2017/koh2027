<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A beneficiary's two-weekly video of their vehicle, with their checklist answers and the
 * evidence that the video is new (see EvaluationTrust).
 */
class Evaluation extends Model
{
    protected $table = 'fleet_evaluations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trust_flags' => 'array',
            'metadata' => 'array',
            'recorded_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'has_damage' => 'boolean',
            'branding_intact' => 'boolean',
            'mileage_km' => 'integer',
            'device_accuracy_m' => 'integer',
            'device_latitude' => 'float',
            'device_longitude' => 'float',
            'metadata_latitude' => 'float',
            'metadata_longitude' => 'float',
            'duration_seconds' => 'float',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'period_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(FleetAdmin::class, 'reviewed_by');
    }

    /**
     * What the beneficiary sees about their own submission.
     */
    public function toBeneficiaryApi(): array
    {
        return [
            'id' => $this->id,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'processing_status' => $this->processing_status,
            'review_status' => $this->review_status,
            'review_notes' => $this->review_status === 'rejected' ? $this->review_notes : null,
        ];
    }

    public function toAdminApi(): array
    {
        $base = "/api/v1/admin/evaluations/{$this->id}";

        return [
            'id' => $this->id,
            'source' => $this->source,
            'challenge_code' => $this->challenge_code,
            'trust_level' => $this->trust_level,
            'trust_flags' => $this->trust_flags ?? [],
            'processing_status' => $this->processing_status,
            'processing_error' => $this->processing_error,
            'video_url' => $this->video_path ? "{$base}/video" : null,
            'thumbnail_url' => $this->thumbnail_path ? "{$base}/thumbnail" : null,
            'duration_seconds' => $this->duration_seconds,
            'original_filename' => $this->original_filename,
            'original_size' => $this->original_size,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'device_location' => $this->device_latitude !== null
                ? ['latitude' => $this->device_latitude, 'longitude' => $this->device_longitude, 'accuracy_m' => $this->device_accuracy_m]
                : null,
            'metadata_location' => $this->metadata_latitude !== null
                ? ['latitude' => $this->metadata_latitude, 'longitude' => $this->metadata_longitude]
                : null,
            'device' => collect([$this->metadata['make'] ?? null, $this->metadata['model'] ?? null])->filter()->join(' ') ?: null,
            'mileage_km' => $this->mileage_km,
            'fuel_level' => $this->fuel_level,
            'has_damage' => $this->has_damage,
            'damage_notes' => $this->damage_notes,
            'branding_intact' => $this->branding_intact,
            'branding_notes' => $this->branding_notes,
            'notes' => $this->notes,
            'review_status' => $this->review_status,
            'review_notes' => $this->review_notes,
            'reviewed_by' => $this->relationLoaded('reviewer') ? $this->reviewer?->name : null,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'vehicle' => $this->relationLoaded('vehicle') ? $this->vehicle->toApi() : null,
            'beneficiary' => $this->relationLoaded('beneficiary') ? $this->beneficiary->toApi() : null,
            'period' => $this->relationLoaded('period') ? [
                'id' => $this->period->id,
                'sequence' => $this->period->sequence,
                'starts_at' => $this->period->starts_at->toIso8601String(),
                'ends_at' => $this->period->ends_at->toIso8601String(),
                'status' => $this->period->status,
            ] : null,
        ];
    }
}
