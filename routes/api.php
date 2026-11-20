<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

/*
| BizDesk REST API, v1. Every route below /auth/* requires a Sanctum token:
|   Authorization: Bearer <token>
| Money values are integers in minor units (kobo/cents).
*/
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('products', ProductController::class);
        Route::post('/products/{product}/restock', [ProductController::class, 'restock']);

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('sales', SaleController::class)->except('update'); // sales are immutable: void + re-sell
        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);

        Route::get('/reports/summary', [ReportController::class, 'summary']);
    });
});
