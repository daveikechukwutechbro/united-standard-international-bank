<?php

declare(strict_types=1);

use App\Domain\ISO8583\Services\AuthorizationHandler;
use App\Domain\ISO8583\Services\MessageCodec;
use App\Domain\ISO8583\Services\ReversalHandler;
use App\Domain\ISO8583\Services\SettlementHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/iso8583')->name('api.iso8583.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::post('/authorize', function (Request $request): JsonResponse {
        $request->validate(['message' => ['required', 'string']]);
        $codec = app(MessageCodec::class);
        $handler = app(AuthorizationHandler::class);
        $incoming = $codec->decode($request->input('message'));
        $response = $handler->handleRequest($incoming);

        return response()->json(['success' => true, 'data' => ['response' => $codec->encode($response)]]);
    })->name('authorize');

    Route::post('/reverse', function (Request $request): JsonResponse {
        $request->validate(['message' => ['required', 'string']]);
        $codec = app(MessageCodec::class);
        $handler = app(ReversalHandler::class);
        $incoming = $codec->decode($request->input('message'));
        $response = $handler->handleRequest($incoming);

        return response()->json(['success' => true, 'data' => ['response' => $codec->encode($response)]]);
    })->name('reverse');

    Route::post('/settle', function (Request $request): JsonResponse {
        $request->validate(['message' => ['required', 'string']]);
        $codec = app(MessageCodec::class);
        $handler = app(SettlementHandler::class);
        $incoming = $codec->decode($request->input('message'));
        $response = $handler->handleRequest($incoming);

        return response()->json(['success' => true, 'data' => ['response' => $codec->encode($response)]]);
    })->name('settle');
});
