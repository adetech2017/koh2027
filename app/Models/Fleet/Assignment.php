<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A vehicle in a beneficiary's hands, from the handover form to the return form.
 */
class Assignment extends Model
{
    protected $table = 'fleet_assignments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'handed_over_at' => 'datetime',
            'returned_at' => 'datetime',
            'recalled_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    public function recall(): BelongsTo
    {
        return $this->belongsTo(Recall::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class);
    }

    public function handoverForm(): HasOne
    {
        return $this->hasOne(Handover::class)->where('type', 'handover');
    }

    public function returnForm(): HasOne
    {
        return $this->hasOne(Handover::class)->where('type', 'return');
    }

    public function evaluationPeriods(): HasMany
    {
        return $this->hasMany(EvaluationPeriod::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * active, recalled (still out, awaiting return) or returned.
     */
    public function displayStatus(): string
    {
        if ($this->status === 'returned') {
            return 'returned';
        }

        return $this->recall_id ? 'recalled' : 'active';
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->displayStatus(),
            'handed_over_at' => $this->handed_over_at?->toIso8601String(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'recalled_at' => $this->recalled_at?->toIso8601String(),
            'vehicle' => $this->relationLoaded('vehicle') ? $this->vehicle?->toApi() : null,
            'beneficiary' => $this->relationLoaded('beneficiary') ? $this->beneficiary?->toApi() : null,
            'recall' => $this->relationLoaded('recall') && $this->recall
                ? ['id' => $this->recall->id, 'title' => $this->recall->title, 'return_by' => $this->recall->return_by->toDateString()]
                : null,
            'handover_id' => $this->relationLoaded('handoverForm') ? $this->handoverForm?->id : null,
            'return_id' => $this->relationLoaded('returnForm') ? $this->returnForm?->id : null,
        ];
    }
}
