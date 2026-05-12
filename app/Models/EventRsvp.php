<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['event_id', 'name', 'email', 'phone', 'lga', 'token', 'status', 'confirmed_at'])]
class EventRsvp extends Model
{
    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function booted(): void
    {
        static::creating(function (EventRsvp $rsvp) {
            $rsvp->token = Str::random(40);
        });
    }
}
