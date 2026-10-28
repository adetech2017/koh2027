<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverPhoto extends Model
{
    protected $table = 'fleet_handover_photos';

    protected $guarded = [];

    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class);
    }
}
