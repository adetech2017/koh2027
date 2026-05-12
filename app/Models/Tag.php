<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasSlug;

    #[\Illuminate\Database\Eloquent\Attributes\Fillable(['name', 'slug', 'color'])]
    protected $fillable = ['name', 'slug', 'color'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, 'taggable');
    }

    public function volunteers(): MorphToMany
    {
        return $this->morphedByMany(Volunteer::class, 'taggable');
    }
}
