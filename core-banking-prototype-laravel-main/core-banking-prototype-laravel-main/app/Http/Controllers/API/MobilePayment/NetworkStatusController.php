<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MobilePayment;

use App\Domain\MobilePayment\Services\NetworkAvailabilityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class NetworkStatusController extends Controller
{
    public function __construct(
        private readonly NetworkAvailabilityService $networkAvailabilityService,
    ) {
    }

    /**
     * Get the status of all supported payment networks.
     *
     * GET /v1/networks/status
     */
    #[OA\Get(
        path: '/api/v1/networks/status',
        operationId: 'mobilePaymentNetworkStatus',
        summary: 'Get the status of all supported payment networks',
        description: 'Returns the current availability and health status of all supported payment networks (e.g., Solana, Tron). Useful for checking network congestion or downtime before initiating payments.',
        tags: ['Mobile Payments'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Network statuses',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'networks', type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(property: 'network', type: 'string', example: 'SOLANA'),
        new OA\Property(property: 'status', type: 'string', enum: ['operational', 'degraded', 'down'], example: 'operational'),
        new OA\Property(property: 'latency_ms', type: 'integer', example: 120),
        new OA\Property(property: 'block_height', type: 'integer', example: 245000000),
        ])),
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
    public function __invoke(): JsonResponse
    {
        $statuses = $this->networkAvailabilityService->getNetworkStatuses();

        return response()->json([
            'success' => true,
            'data'    => [
                'networks' => $statuses,
            ],
        ]);
    }
}
