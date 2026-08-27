<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\SignedUrlController;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routing prefix ('api/v1') and name prefix ('api.v1.') are configured
| centrally in bootstrap/app.php.
| Rate limiting ('throttle:api') is applied globally to the 'api' group.
|
*/

// System Health & Liveness Probe
Route::get('health', HealthController::class)->name('health');

// Authentication Routes
Route::prefix('auth')->as('auth.')->group(function (): void {
    // Public routes (Rate limited to 10 attempts/min)
    Route::middleware('throttle:api.auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::match(['put', 'patch'], 'me', [AuthController::class, 'updateMe'])->name('update-me');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
    });
});

// User Management CRUD Routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('users', UserController::class);
    Route::post('signed-url', SignedUrlController::class)->name('signed-url');
});

// Local Development Signed Upload Handler (Signature verified via 'signed' middleware)
Route::match(['put', 'post'], 'media/upload', [MediaController::class, 'upload'])
    ->middleware('signed')
    ->name('media.upload');
