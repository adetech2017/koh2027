<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The signed form for a handover (vehicle goes out) or a return (vehicle comes back).
 */
class Handover extends Model
{
    protected $table = 'fleet_handovers';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'odometer_km' => 'integer',
            'location_accuracy_m' => 'integer',
            'occurred_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(FleetAdmin::class, 'officer_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HandoverPhoto::class);
    }

    public function reference(): string
    {
        return ($this->type === 'return' ? 'RET-' : 'HO-').str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function toApi(): array
    {
        $base = "/api/v1/admin/handovers/{$this->id}";

        return [
            'id' => $this->id,
            'reference' => $this->reference(),
            'type' => $this->type,
            'assignment_id' => $this->assignment_id,
            'odometer_km' => $this->odometer_km,
            'fuel_level' => $this->fuel_level,
            'checklist' => $this->checklist,
            'notes' => $this->notes,
            'officer' => $this->officer?->name,
            'signed_by_name' => $this->signed_by_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_accuracy_m' => $this->location_accuracy_m,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'officer_signature_url' => "{$base}/signature/officer",
            'signer_signature_url' => "{$base}/signature/signer",
            'receipt_url' => "{$base}/receipt",
            'photos' => $this->photos->map(fn (HandoverPhoto $photo) => [
                'id' => $photo->id,
                'url' => "{$base}/photos/{$photo->id}",
            ])->all(),
        ];
    }
}
