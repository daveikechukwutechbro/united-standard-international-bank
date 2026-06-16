<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Interledger;

use App\Domain\Interledger\Services\QuoteService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
    ) {
    }

    /**
     * Get a cross-currency quote for an ILP payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'send_asset'    => ['required', 'string', 'size:3'],
            'receive_asset' => ['required', 'string', 'size:3'],
            'send_amount'   => ['required', 'numeric', 'gt:0'],
        ]);

        $quote = $this->quoteService->getQuote(
            sendAsset: strtoupper($validated['send_asset']),
            receiveAsset: strtoupper($validated['receive_asset']),
            sendAmount: (string) $validated['send_amount'],
        );

        return response()->json($quote);
    }

    /**
     * List the assets supported by this ILP connector.
     */
    public function supportedAssets(): JsonResponse
    {
        return response()->json([
            'assets' => $this->quoteService->getSupportedAssets(),
        ]);
    }
}
