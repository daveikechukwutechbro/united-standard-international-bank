<?php

declare(strict_types=1);

use App\Domain\ISO20022\Services\MessageGenerator;
use App\Domain\ISO20022\Services\MessageParser;
use App\Domain\ISO20022\Services\MessageRegistry;
use App\Domain\ISO20022\Services\MessageValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/iso20022')->name('api.iso20022.')->middleware(['auth:sanctum'])->group(function (): void {
    Route::post('/validate', function (Request $request): JsonResponse {
        $request->validate(['xml' => ['required', 'string']]);
        $validator = app(MessageValidator::class);

        return response()->json(['success' => true, 'data' => $validator->validate($request->input('xml'))]);
    })->name('validate');

    Route::post('/parse', function (Request $request): JsonResponse {
        $request->validate(['xml' => ['required', 'string']]);
        $parser = app(MessageParser::class);
        $generator = app(MessageGenerator::class);
        $dto = $parser->parseXml($request->input('xml'));

        return response()->json(['success' => true, 'data' => $generator->toArray($dto)]);
    })->name('parse');

    Route::post('/generate', function (Request $request): JsonResponse {
        $request->validate([
            'message_type' => ['required', 'string'],
            'data'         => ['required', 'array'],
        ]);
        $generator = app(MessageGenerator::class);
        $xml = $generator->generateFromArray(
            $request->input('message_type'),
            $request->input('data'),
        );

        return response()->json(['success' => true, 'data' => ['xml' => $xml]]);
    })->name('generate');

    Route::get('/supported-types', function (): JsonResponse {
        return response()->json(['success' => true, 'data' => app(MessageRegistry::class)->supportedTypes()]);
    })->name('supported-types');
});
