<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MobilePayment;

use App\Domain\MobilePayment\Services\TransactionDetailService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionDetailService $transactionDetailService,
    ) {
    }

    /**
     * Get transaction details.
     *
     * GET /v1/transactions/{txId}
     */
    #[OA\Get(
        path: '/api/v1/transactions/{txId}',
        operationId: 'mobilePaymentGetTransaction',
        summary: 'Get transaction details',
        description: 'Retrieves the full details of a transaction including on-chain status, merchant info, amount, and fees.',
        tags: ['Mobile Payments'],
        security: [['sanctum' => []]],
        parameters: [
        new OA\Parameter(name: 'txId', in: 'path', required: true, description: 'Transaction ID', schema: new OA\Schema(type: 'string', example: 'tx_abc123')),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Transaction details',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'transactionId', type: 'string', example: 'tx_abc123'),
        new OA\Property(property: 'status', type: 'string', example: 'CONFIRMED'),
        new OA\Property(property: 'amount', type: 'number', example: 25.50),
        new OA\Property(property: 'asset', type: 'string', example: 'USDC'),
        new OA\Property(property: 'network', type: 'string', example: 'SOLANA'),
        new OA\Property(property: 'hash', type: 'string', description: 'On-chain transaction hash'),
        new OA\Property(property: 'merchant', type: 'object', properties: [
        new OA\Property(property: 'displayName', type: 'string', example: 'Coffee Shop'),
        ]),
        new OA\Property(property: 'fee', type: 'object', properties: [
        new OA\Property(property: 'amount', type: 'number', example: 0.01),
        new OA\Property(property: 'asset', type: 'string', example: 'USDC'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
        ]),
        ])
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'object', properties: [
        new OA\Property(property: 'code', type: 'string', example: 'UNAUTHORIZED'),
        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
        ]),
        ])
    )]
    #[OA\Response(
        response: 404,
        description: 'Transaction not found',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'object', properties: [
        new OA\Property(property: 'code', type: 'string', example: 'TRANSACTION_NOT_FOUND'),
        new OA\Property(property: 'message', type: 'string', example: 'Transaction not found.'),
        ]),
        ])
    )]
    public function show(Request $request, string $txId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $details = $this->transactionDetailService->getDetails(
            $txId,
            $user->id,
        );

        if (! $details) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'TRANSACTION_NOT_FOUND',
                    'message' => 'Transaction not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $details,
        ]);
    }
}
