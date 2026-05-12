<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConstituentNote extends Model
{
    #[\Illuminate\Database\Eloquent\Attributes\Fillable(['notable_type', 'notable_id', 'author_id', 'body'])]
    protected $fillable = ['notable_type', 'notable_id', 'author_id', 'body'];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
