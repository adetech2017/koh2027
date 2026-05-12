<?php

namespace App\Http\Controllers\Api;

use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;

class ApiHeroSlideController
{
    public function index(): JsonResponse
    {
        $slides = HeroSlide::active()->get();

        return response()->json([
            'data' => $slides->map(fn ($s) => [...$s->toArray(), 'image_url' => $s->image_url]),
        ]);
    }
}
