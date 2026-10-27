<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'fleet_otps';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
