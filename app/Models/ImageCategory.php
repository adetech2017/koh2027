<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

// A gallery album, usually one per campaign event (shown as "albums" everywhere in the UI)
#[Fillable(['name', 'event_date', 'description'])]
class ImageCategory extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            // Shared links to an album keep working after it is renamed
            ->doNotGenerateSlugsOnUpdate();
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'category_id');
    }

    // Albums with a visible photo, newest event first. Albums without a date sort by their latest upload.
    public function scopeForPublic(Builder $query): Builder
    {
        return $query
            ->withCount(['galleryImages' => fn ($q) => $q->where('is_active', true)])
            ->withMax(['galleryImages' => fn ($q) => $q->where('is_active', true)], 'created_at')
            ->whereHas('galleryImages', fn ($q) => $q->where('is_active', true))
            ->orderByRaw('COALESCE(event_date, DATE(gallery_images_max_created_at)) DESC')
            ->orderByDesc('gallery_images_max_created_at');
    }
}
