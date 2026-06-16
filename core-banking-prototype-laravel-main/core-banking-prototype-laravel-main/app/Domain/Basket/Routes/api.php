<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BasketAccountController;
use App\Http\Controllers\Api\BasketController;
use App\Http\Controllers\Api\BasketPerformanceController;
use Illuminate\Support\Facades\Route;

// Basket endpoints
Route::prefix('v2')->group(function () {
    // V2 accounts endpoint (requires authentication)
    Route::middleware('auth:sanctum')->get('/accounts', [App\Http\Controllers\Api\AccountController::class, 'index']);

    // Public basket endpoints
    Route::prefix('baskets')->group(function () {
        Route::get('/', [BasketController::class, 'index']);
        Route::get('/{code}', [BasketController::class, 'show']);
        Route::get('/{code}/value', [BasketController::class, 'getValue']);
        Route::get('/{code}/history', [BasketController::class, 'getHistory']);

        // Performance tracking endpoints
        Route::get('/{code}/performance', [BasketPerformanceController::class, 'show']);
        Route::get('/{code}/performance/history', [BasketPerformanceController::class, 'history']);
        Route::get('/{code}/performance/summary', [BasketPerformanceController::class, 'summary']);
        Route::get('/{code}/performance/components', [BasketPerformanceController::class, 'components']);
        Route::get('/{code}/performance/top-performers', [BasketPerformanceController::class, 'topPerformers']);
        Route::get('/{code}/performance/worst-performers', [BasketPerformanceController::class, 'worstPerformers']);
        Route::get('/{code}/performance/compare', [BasketPerformanceController::class, 'compare']);
    });

    // Protected basket endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/baskets', [BasketController::class, 'store']);
        Route::post('/baskets/{code}/rebalance', [BasketController::class, 'rebalance']);
        Route::post('/baskets/{code}/performance/calculate', [BasketPerformanceController::class, 'calculate']);

        // Basket operations on accounts
        Route::prefix('accounts/{uuid}/baskets')->group(function () {
            Route::get('/', [BasketAccountController::class, 'getBasketHoldings']);
            Route::post('/decompose', [BasketAccountController::class, 'decompose']);
            Route::post('/compose', [BasketAccountController::class, 'compose']);
        });
    });
});
