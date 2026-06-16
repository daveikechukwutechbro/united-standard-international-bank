<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Microfinance\MicrofinanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/microfinance')->name('api.microfinance.')->middleware(['auth:sanctum'])->group(function () {
    // Groups
    Route::post('/groups', [MicrofinanceController::class, 'createGroup'])->name('groups.create');
    Route::post('/groups/{id}/activate', [MicrofinanceController::class, 'activateGroup'])->name('groups.activate');
    Route::post('/groups/{id}/close', [MicrofinanceController::class, 'closeGroup'])->name('groups.close');
    Route::post('/groups/{id}/members', [MicrofinanceController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/groups/{id}/members/{userId}', [MicrofinanceController::class, 'removeMember'])->name('groups.members.remove');
    Route::get('/groups/{id}/members', [MicrofinanceController::class, 'listMembers'])->name('groups.members.list');
    Route::post('/groups/{id}/meetings', [MicrofinanceController::class, 'recordMeeting'])->name('groups.meetings.record');

    // Share Accounts
    Route::get('/share-accounts', [MicrofinanceController::class, 'listShareAccounts'])->name('share-accounts.list');
    Route::post('/share-accounts', [MicrofinanceController::class, 'openShareAccount'])->name('share-accounts.open');
    Route::post('/share-accounts/{id}/purchase', [MicrofinanceController::class, 'purchaseShares'])->name('share-accounts.purchase');
    Route::post('/share-accounts/{id}/redeem', [MicrofinanceController::class, 'redeemShares'])->name('share-accounts.redeem');

    // Provisioning
    Route::get('/provisioning', [MicrofinanceController::class, 'getProvisionSummary'])->name('provisioning.summary');
    Route::get('/provisioning/{category}', [MicrofinanceController::class, 'getProvisionsByCategory'])->name('provisioning.by-category');

    // Collection Sheets
    Route::post('/collection-sheets', [MicrofinanceController::class, 'generateCollectionSheet'])->name('collection-sheets.generate');
});
