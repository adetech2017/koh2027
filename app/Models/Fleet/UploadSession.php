<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A video being uploaded in pieces (see ChunkedUploads).
 */
class UploadSession extends Model
{
    use HasUuids;

    protected $table = 'fleet_upload_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'period_id');
    }

    /**
     * The expected size of piece $index (all full-size except possibly the last).
     */
    public function chunkLength(int $index): int
    {
        return $index < $this->total_chunks - 1
            ? $this->chunk_size
            : $this->size - $this->chunk_size * ($this->total_chunks - 1);
    }
}
