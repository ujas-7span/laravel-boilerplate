<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Developer\DeveloperController;

/*
|--------------------------------------------------------------------------
| Developer Portal Routes
|--------------------------------------------------------------------------
|
| Protected developer dashboard and internal tools.
| Authentication is managed via native Laravel sessions and environment variables.
|
*/

Route::redirect('/', '/developer/login');
Route::get('login', [DeveloperController::class, 'loginPage'])->name('login');
Route::post('login', [DeveloperController::class, 'login'])->name('login.submit');

Route::middleware('developer')->group(function (): void {
    Route::get('dashboard', [DeveloperController::class, 'dashboard'])->name('dashboard');
    Route::post('logout', [DeveloperController::class, 'logout'])->name('logout');
});
