<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A committee decision to call a batch of vehicles back by a date.
 */
class Recall extends Model
{
    protected $table = 'fleet_recalls';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'return_by' => 'date',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(FleetAdmin::class, 'created_by');
    }

    public function toApi(): array
    {
        $total = $this->assignments_count ?? null;
        $returned = $this->returned_count ?? null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'reason' => $this->reason,
            'return_by' => $this->return_by->toDateString(),
            'overdue' => $returned !== null && $returned < $total && $this->return_by->endOfDay()->isPast(),
            'vehicles_total' => $total,
            'vehicles_returned' => $returned,
            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
