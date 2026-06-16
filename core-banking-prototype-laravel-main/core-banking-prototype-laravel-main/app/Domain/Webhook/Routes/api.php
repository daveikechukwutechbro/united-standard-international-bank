<?php

declare(strict_types=1);

use App\Domain\Webhook\Services\WebhookTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/webhooks')->name('api.webhooks.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::get('/events', function (): JsonResponse {
        $service = app(WebhookTestService::class);

        return response()->json(['success' => true, 'data' => $service->getAvailableEvents()]);
    })->name('events');

    Route::post('/test/{eventType}', function (Request $request, string $eventType): JsonResponse {
        $service = app(WebhookTestService::class);
        $payload = $service->generateTestPayload($eventType);

        return response()->json(['success' => true, 'data' => $payload]);
    })->name('test');

    Route::get('/deliveries', function (): JsonResponse {
        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Delivery log not yet populated — webhooks will appear here after first delivery',
        ]);
    })->name('deliveries.index');

    Route::get('/deliveries/{id}', function (string $id): JsonResponse {
        return response()->json([
            'success' => false,
            'error'   => ['code' => 'NOT_FOUND', 'message' => 'Delivery not found'],
        ], 404);
    })->name('deliveries.show');
});
