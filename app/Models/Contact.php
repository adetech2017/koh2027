<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'ip_address', 'status', 'read_at'])]
class Contact extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
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
