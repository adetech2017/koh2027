<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class EvaluationChallenge extends Model
{
    protected $table = 'fleet_evaluation_challenges';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
