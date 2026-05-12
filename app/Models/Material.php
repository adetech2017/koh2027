<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'category', 'file_path', 'file_name', 'file_type', 'file_size', 'thumbnail_path', 'download_count', 'is_active'])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'download_count' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('created_at');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $kb = $this->file_size / 1024;
        return $kb < 1024 ? round($kb, 1).' KB' : round($kb / 1024, 1).' MB';
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }
}
