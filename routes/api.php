<?php

use App\Http\Controllers\Api\ApiEventController;
use App\Http\Controllers\Api\ApiHeroSlideController;
use App\Http\Controllers\Api\ApiMerchandiseController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [ApiEventController::class, 'index']);
Route::get('/merchandise', [ApiMerchandiseController::class, 'index']);
Route::get('/hero-slides', [ApiHeroSlideController::class, 'index']);
