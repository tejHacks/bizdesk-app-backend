<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Web admin for business owners (session login, same accounts as the API).
Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [DashboardController::class, 'showLogin'])->name('login');
    Route::post('/login', [DashboardController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');
});
