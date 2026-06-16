<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Interledger;

use App\Domain\Interledger\Services\OpenPaymentsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly OpenPaymentsService $openPaymentsService,
    ) {
    }

    /**
     * Create an Open Payments incoming payment resource.
     */
    public function createIncoming(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_address' => ['required', 'string'],
            'amount'         => ['required', 'numeric', 'gt:0'],
            'asset_code'     => ['required', 'string', 'size:3'],
        ]);

        $result = $this->openPaymentsService->createIncomingPayment(
            walletAddress: $validated['wallet_address'],
            amount: (string) $validated['amount'],
            assetCode: strtoupper($validated['asset_code']),
        );

        return response()->json($result, 201);
    }

    /**
     * Create an Open Payments outgoing payment resource.
     */
    public function createOutgoing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_address' => ['required', 'string'],
            'quote_id'       => ['required', 'string', 'uuid'],
            'grant_token'    => ['required', 'string'],
        ]);

        $result = $this->openPaymentsService->createOutgoingPayment(
            walletAddress: $validated['wallet_address'],
            quoteId: $validated['quote_id'],
            grantToken: $validated['grant_token'],
        );

        return response()->json($result, 201);
    }

    /**
     * Get the status of a payment by its ID.
     */
    public function status(string $id): JsonResponse
    {
        $result = $this->openPaymentsService->getPaymentStatus($id);

        return response()->json($result);
    }
}
