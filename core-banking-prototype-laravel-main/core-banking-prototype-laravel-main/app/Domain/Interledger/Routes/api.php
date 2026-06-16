<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Interledger\ConnectionController;
use App\Http\Controllers\Api\Interledger\PaymentController;
use App\Http\Controllers\Api\Interledger\QuoteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/interledger')->middleware('auth:sanctum')->group(function (): void {
    // Cross-currency quotes
    Route::post('/quotes', [QuoteController::class, 'store']);
    Route::get('/supported-assets', [QuoteController::class, 'supportedAssets']);

    // Open Payments — incoming / outgoing payments
    Route::post('/payments/incoming', [PaymentController::class, 'createIncoming']);
    Route::post('/payments/outgoing', [PaymentController::class, 'createOutgoing']);
    Route::get('/payments/{id}', [PaymentController::class, 'status']);

    // ILP STREAM connections
    Route::post('/connections', [ConnectionController::class, 'store']);
    Route::delete('/connections/{id}', [ConnectionController::class, 'destroy']);
});
