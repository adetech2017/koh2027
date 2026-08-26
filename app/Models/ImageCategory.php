<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class ImageCategory extends Model
{
    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'category_id');
    }
}
