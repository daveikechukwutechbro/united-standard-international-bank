<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Compliance;

use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Get(
    path: '/api/v1/compliance/check-address',
    operationId: 'complianceCheckAddress',
    summary: 'Screen a blockchain address against sanctions lists',
    description: 'Checks whether a blockchain address appears on any sanctions lists using the configured screening provider.',
    tags: ['Compliance'],
    security: [['sanctum' => []]],
    parameters: [
    new OA\Parameter(name: 'address', in: 'query', required: true, description: 'The blockchain address to screen', schema: new OA\Schema(type: 'string', example: '0x1234567890abcdef1234567890abcdef12345678')),
    new OA\Parameter(name: 'network', in: 'query', required: false, description: 'Blockchain network (default: ethereum)', schema: new OA\Schema(type: 'string', example: 'ethereum')),
    ]
)]
#[OA\Response(
    response: 200,
    description: 'Sanctions check result',
    content: new OA\JsonContent(properties: [
    new OA\Property(property: 'success', type: 'boolean', example: true),
    new OA\Property(property: 'data', type: 'object', properties: [
    new OA\Property(property: 'sanctioned', type: 'boolean', example: false),
    new OA\Property(property: 'risk_score', type: 'string', example: 'low'),
    new OA\Property(property: 'provider', type: 'string', example: 'chainalysis'),
    new OA\Property(property: 'details', type: 'object'),
    ]),
    ])
)]
#[OA\Response(
    response: 401,
    description: 'Unauthorized'
)]
#[OA\Response(
    response: 422,
    description: 'Validation error'
)]
#[OA\Response(
    response: 429,
    description: 'Rate limit exceeded'
)]
#[OA\Response(
    response: 503,
    description: 'Screening service unavailable'
)]
class SanctionsCheckController extends Controller
{
    public function __construct(
        private readonly SanctionsScreeningInterface $screening,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'address' => ['required', 'string', 'min:10'],
            'network' => ['nullable', 'string'],
        ]);

        $address = $request->input('address');
        $network = $request->input('network', 'ethereum');

        try {
            $result = $this->screening->screenAddress($address, $network);

            $sanctioned = ($result['total_matches'] ?? 0) > 0;
            $riskScore = $sanctioned ? 'high' : 'low';

            return response()->json([
                'success' => true,
                'data'    => [
                    'sanctioned'    => $sanctioned,
                    'risk_score'    => $riskScore,
                    'provider'      => $this->screening->getName(),
                    'lists_checked' => $result['lists_checked'] ?? [],
                    'total_matches' => $result['total_matches'] ?? 0,
                    'details'       => $result['matches'] ?? [],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Sanctions screening failed', [
                'address' => $address,
                'network' => $network,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'SCREENING_UNAVAILABLE',
                    'message' => 'Sanctions screening service is temporarily unavailable.',
                ],
            ], 503);
        }
    }
}
