<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'lga', 'ward', 'occupation', 'motivation', 'skills', 'has_vehicle', 'status', 'approved_at', 'ip_address'])]
class Volunteer extends Model
{
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'has_vehicle' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(ConstituentNote::class, 'notable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
