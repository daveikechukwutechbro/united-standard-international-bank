<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\UserVotingController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Support\Facades\Route;

// Governance endpoints
Route::middleware('auth:sanctum')->group(function () {
    // Governance endpoints (query rate limiting for reads, vote rate limiting for votes)
    Route::prefix('polls')->group(function () {
        Route::middleware('api.rate_limit:query')->group(function () {
            Route::get('/', [PollController::class, 'index']);
            Route::get('/active', [PollController::class, 'active']);
            Route::get('/{uuid}', [PollController::class, 'show']);
            Route::get('/{uuid}/results', [PollController::class, 'results']);
            Route::get('/{uuid}/voting-power', [PollController::class, 'votingPower']);
        });

        Route::middleware('api.rate_limit:admin')->group(function () {
            Route::post('/', [PollController::class, 'store']);
            Route::post('/{uuid}/activate', [PollController::class, 'activate']);
        });

        Route::post('/{uuid}/vote', [PollController::class, 'vote'])->middleware('transaction.rate_limit:vote');
    });

    Route::prefix('votes')->middleware('api.rate_limit:query')->group(function () {
        Route::get('/', [VoteController::class, 'index']);
        Route::get('/stats', [VoteController::class, 'stats']);
        Route::get('/{id}', [VoteController::class, 'show']);
        Route::post('/{id}/verify', [VoteController::class, 'verify']);
    });

    // User-friendly voting interface
    Route::prefix('voting')->group(function () {
        Route::middleware('api.rate_limit:query')->group(function () {
            Route::get('/polls', [UserVotingController::class, 'getActivePolls']);
            Route::get('/polls/upcoming', [UserVotingController::class, 'getUpcomingPolls']);
            Route::get('/polls/history', [UserVotingController::class, 'getVotingHistory']);
            Route::get('/dashboard', [UserVotingController::class, 'getDashboard']);
        });

        Route::post('/polls/{uuid}/vote', [UserVotingController::class, 'submitBasketVote'])->middleware('transaction.rate_limit:vote');
    });
});
