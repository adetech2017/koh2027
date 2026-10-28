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

    Route::middleware(['auth:fleet_beneficiary', 'fleet.active:fleet_beneficiary'])->group(function () {
        Route::get('vehicle', [Beneficiary\VehicleController::class, 'show'])->name('vehicle');

        Route::get('evaluations', [Beneficiary\EvaluationController::class, 'index'])->name('evaluations.index');
        Route::post('evaluations', [Beneficiary\EvaluationController::class, 'store'])->name('evaluations.store')->middleware('throttle:20,60');
        Route::post('evaluations/challenge', [Beneficiary\EvaluationController::class, 'challenge'])->name('evaluations.challenge')->middleware('throttle:30,60');
        Route::post('evaluations/uploads', [Beneficiary\EvaluationController::class, 'startUpload'])->name('evaluations.uploads.start')->middleware('throttle:30,60');
        Route::get('evaluations/uploads/{upload}', [Beneficiary\EvaluationController::class, 'uploadStatus'])->name('evaluations.uploads.show');
        Route::put('evaluations/uploads/{upload}/chunks/{index}', [Beneficiary\EvaluationController::class, 'putChunk'])->name('evaluations.uploads.chunk')
            ->whereNumber('index')->middleware('throttle:600,60');
    });

    // Admin dashboard
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('auth/login', [Admin\AuthController::class, 'login'])->name('login')->middleware('throttle:fleet-admin-login');

        Route::middleware(['auth:fleet_admin', 'fleet.active:fleet_admin'])->group(function () {
            Route::get('auth/me', [Admin\AuthController::class, 'me'])->name('me');
            Route::post('auth/logout', [Admin\AuthController::class, 'logout'])->name('logout');

            // Every admin role can look
            Route::get('options', [Admin\MetaController::class, 'options'])->name('options');
            Route::get('dashboard', [Admin\MetaController::class, 'dashboard'])->name('dashboard');
            Route::get('beneficiaries', [Admin\BeneficiaryController::class, 'index'])->name('beneficiaries.index');
            Route::get('beneficiaries/{beneficiary}', [Admin\BeneficiaryController::class, 'show'])->name('beneficiaries.show')->withTrashed();
            Route::get('vehicles', [Admin\VehicleController::class, 'index'])->name('vehicles.index');
            Route::get('vehicles/{vehicle}', [Admin\VehicleController::class, 'show'])->name('vehicles.show')->withTrashed();
            Route::get('assignments', [Admin\AssignmentController::class, 'index'])->name('assignments.index');
            Route::get('assignments/{assignment}', [Admin\AssignmentController::class, 'show'])->name('assignments.show');
            Route::get('handovers/{handover}', [Admin\HandoverController::class, 'show'])->name('handovers.show');
            Route::get('handovers/{handover}/receipt', [Admin\HandoverController::class, 'receipt'])->name('handovers.receipt');
            Route::get('handovers/{handover}/photos/{photo}', [Admin\HandoverController::class, 'photo'])->name('handovers.photo');
            Route::get('handovers/{handover}/signature/{party}', [Admin\HandoverController::class, 'signature'])->name('handovers.signature')->whereIn('party', ['officer', 'signer']);
            Route::get('evaluations', [Admin\EvaluationController::class, 'index'])->name('evaluations.index');
            Route::get('evaluations/{evaluation}', [Admin\EvaluationController::class, 'show'])->name('evaluations.show');
            Route::get('evaluations/{evaluation}/video', [Admin\EvaluationController::class, 'video'])->name('evaluations.video');
            Route::get('evaluations/{evaluation}/thumbnail', [Admin\EvaluationController::class, 'thumbnail'])->name('evaluations.thumbnail');
            Route::get('recalls', [Admin\RecallController::class, 'index'])->name('recalls.index');
            Route::get('recalls/{recall}', [Admin\RecallController::class, 'show'])->name('recalls.show');
            Route::get('import/{type}/template', [Admin\ImportController::class, 'template'])->name('import.template')->whereIn('type', ['beneficiaries', 'vehicles']);

            // Super admins and fleet officers can change things; reviewers can't
            Route::middleware('fleet.role:super_admin,fleet_officer')->group(function () {
                Route::post('beneficiaries', [Admin\BeneficiaryController::class, 'store'])->name('beneficiaries.store');
                Route::patch('beneficiaries/{beneficiary}', [Admin\BeneficiaryController::class, 'update'])->name('beneficiaries.update');
                Route::post('vehicles', [Admin\VehicleController::class, 'store'])->name('vehicles.store');
                Route::patch('vehicles/{vehicle}', [Admin\VehicleController::class, 'update'])->name('vehicles.update');
                Route::post('import/{type}', [Admin\ImportController::class, 'store'])->name('import.store')->whereIn('type', ['beneficiaries', 'vehicles']);
                Route::post('handovers', [Admin\HandoverController::class, 'store'])->name('handovers.store');
                Route::post('assignments/{assignment}/return', [Admin\HandoverController::class, 'storeReturn'])->name('assignments.return');
                Route::post('recalls', [Admin\RecallController::class, 'store'])->name('recalls.store');
            });

            Route::middleware('fleet.role:super_admin')->group(function () {
                Route::get('admins', [Admin\AdminController::class, 'index'])->name('admins.index');
                Route::post('admins', [Admin\AdminController::class, 'store'])->name('admins.store');
                Route::patch('admins/{admin}', [Admin\AdminController::class, 'update'])->name('admins.update');
            });
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
