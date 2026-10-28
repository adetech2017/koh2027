<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One two-week window of an assignment, in which the beneficiary submits one video evaluation.
 */
class EvaluationPeriod extends Model
{
    protected $table = 'fleet_evaluation_periods';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'period_id');
    }

    public function latestEvaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class, 'period_id')->latestOfMany('submitted_at');
    }

    /**
     * True while a video can still be submitted for this window.
     */
    public function acceptsSubmissions(): bool
    {
        return $this->status === 'open' && now()->between($this->starts_at, $this->ends_at);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status,
            'accepts_submissions' => $this->acceptsSubmissions(),
            'evaluation' => $this->relationLoaded('latestEvaluation') && $this->latestEvaluation
                ? $this->latestEvaluation->toBeneficiaryApi()
                : null,
        ];
    }
}
