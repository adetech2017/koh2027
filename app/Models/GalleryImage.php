<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'alt_text', 'image_path', 'thumbnail_path', 'category_id', 'event_label', 'taken_on', 'is_active', 'is_featured', 'sort_order'])]
class GalleryImage extends Model
{
    protected function casts(): array
    {
        return [
            'taken_on' => 'date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Most recently added first, everywhere photos are listed
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    // What the public site needs to show a photo and its caption
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            // Older uploads stored the filename as the title or description; never show those
            'title' => self::isFilename($this->title) ? null : $this->title,
            'alt_text' => self::isFilename($this->alt_text) ? '' : $this->alt_text,
            'event_label' => $this->event_label,
            'taken_on' => $this->taken_on?->toDateString(),
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            'album' => $this->relationLoaded('category') && $this->category
                ? ['name' => $this->category->name, 'slug' => $this->category->slug]
                : null,
        ];
    }

    public static function isFilename(?string $text): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) $text);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ImageCategory::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }
}
