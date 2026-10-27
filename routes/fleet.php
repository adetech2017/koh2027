<?php

/*
| Fleet app (campaign vehicle monitoring), served on config('fleet.domain').
| Loaded from bootstrap/app.php with the "fleet" middleware group and the "fleet." name prefix.
*/

use App\Http\Controllers\Fleet\Admin;
use App\Http\Controllers\Fleet\Beneficiary;
use App\Http\Controllers\Fleet\SpaController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->name('api.')->group(function () {

    // Beneficiary app
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('request-code', [Beneficiary\AuthController::class, 'requestCode'])->name('request-code')->middleware('throttle:fleet-otp');
        Route::post('verify', [Beneficiary\AuthController::class, 'verify'])->name('verify')->middleware('throttle:fleet-otp-verify');

        Route::middleware(['auth:fleet_beneficiary', 'fleet.active:fleet_beneficiary'])->group(function () {
            Route::get('me', [Beneficiary\AuthController::class, 'me'])->name('me');
            Route::post('logout', [Beneficiary\AuthController::class, 'logout'])->name('logout');
        });
    });

    // Admin dashboard
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('auth/login', [Admin\AuthController::class, 'login'])->name('login')->middleware('throttle:fleet-admin-login');

        Route::middleware(['auth:fleet_admin', 'fleet.active:fleet_admin'])->group(function () {
            Route::get('auth/me', [Admin\AuthController::class, 'me'])->name('me');
            Route::post('auth/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        });
    });

    // Unknown API paths get a JSON 404 instead of falling through to the React apps
    Route::any('{any}', fn () => response()->json(['message' => 'Not found.'], 404))->where('any', '.*');
});

Route::get('admin/{any?}', [SpaController::class, 'admin'])->where('any', '.*')->name('admin');
Route::get('{any?}', [SpaController::class, 'beneficiary'])->where('any', '.*')->name('app');

// Any other method on any other path: stop it reaching the campaign site's form routes.
// (Not Route::any: that also registers GET for the same URI and would replace the route above.)
Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '{any?}', fn () => abort(404))->where('any', '.*');
