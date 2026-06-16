<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use App\Domain\Webhook\Services\WebhookReplayService;
use App\Domain\Webhook\Services\WebhookTestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PartnerWebhookTestController extends Controller
{
    public function __construct(
        private readonly WebhookTestService $testService,
        private readonly WebhookReplayService $replayService,
    ) {
    }

    /**
     * List available test event types.
     *
     * GET /api/partner/v1/webhooks/events
     */
    #[OA\Get(
        path: '/api/partner/v1/webhooks/events',
        operationId: 'partnerWebhookEvents',
        summary: 'List available webhook test event types',
        description: 'Returns all event types that can be used for webhook testing.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Available webhook event types',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string', example: 'payment.completed')),
        ])
    )]
    public function events(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->testService->getAvailableEvents(),
        ]);
    }

    /**
     * Generate and optionally send a test webhook payload.
     *
     * POST /api/partner/v1/webhooks/test/{eventType}
     */
    #[OA\Post(
        path: '/api/partner/v1/webhooks/test/{eventType}',
        operationId: 'partnerWebhookTest',
        summary: 'Send a test webhook for a given event type',
        description: 'Generates a test webhook payload for the specified event type and optionally sends it to the provided URL.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'eventType', in: 'path', required: true, description: 'Webhook event type', schema: new OA\Schema(type: 'string', example: 'payment.completed')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'target_url', type: 'string', nullable: true, example: 'https://example.com/webhook', description: 'URL to send the test webhook to'),
                new OA\Property(property: 'secret', type: 'string', nullable: true, description: 'HMAC secret for signature generation'),
            ])
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Test webhook payload generated (and optionally sent)',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'payload', type: 'object', description: 'Generated test payload'),
                new OA\Property(property: 'sent', type: 'boolean', nullable: true, example: true),
                new OA\Property(property: 'status_code', type: 'integer', nullable: true, example: 200),
            ]),
        ])
    )]
    public function test(Request $request, string $eventType): JsonResponse
    {
        $validated = $request->validate([
            'target_url' => 'nullable|url',
            'secret'     => 'nullable|string|max:255',
        ]);

        $payload = $this->testService->generateTestPayload($eventType);

        $result = [
            'payload' => $payload,
            'sent'    => null,
        ];

        if (! empty($validated['target_url'])) {
            $replayResult = $this->replayService->replay(
                $payload['webhook_id'],
                $validated['target_url'],
                (string) json_encode($payload),
                $validated['secret'] ?? null,
            );

            $result['sent'] = $replayResult['replayed'];
            $result['status_code'] = $replayResult['status_code'];
            $result['error'] = $replayResult['error'];
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Replay a webhook delivery.
     *
     * POST /api/partner/v1/webhooks/replay/{deliveryId}
     */
    #[OA\Post(
        path: '/api/partner/v1/webhooks/replay/{deliveryId}',
        operationId: 'partnerWebhookReplay',
        summary: 'Replay a webhook delivery',
        description: 'Replays a past webhook delivery to the specified target URL.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'deliveryId', in: 'path', required: true, description: 'Webhook delivery ID', schema: new OA\Schema(type: 'string', example: 'whk_test_abc123')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['target_url', 'payload'],
                properties: [
                    new OA\Property(property: 'target_url', type: 'string', example: 'https://example.com/webhook'),
                    new OA\Property(property: 'payload', type: 'string', description: 'JSON payload to replay'),
                    new OA\Property(property: 'secret', type: 'string', nullable: true, description: 'HMAC secret for signature'),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Webhook replayed',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'data', type: 'object', properties: [
                new OA\Property(property: 'replayed', type: 'boolean', example: true),
                new OA\Property(property: 'delivery_id', type: 'string', example: 'whk_test_abc123'),
                new OA\Property(property: 'status_code', type: 'integer', nullable: true, example: 200),
                new OA\Property(property: 'error', type: 'string', nullable: true),
            ]),
        ])
    )]
    public function replay(Request $request, string $deliveryId): JsonResponse
    {
        $validated = $request->validate([
            'target_url' => 'required|url',
            'payload'    => 'required|string',
            'secret'     => 'nullable|string|max:255',
        ]);

        $result = $this->replayService->replay(
            $deliveryId,
            $validated['target_url'],
            $validated['payload'],
            $validated['secret'] ?? null,
        );

        return response()->json([
            'success' => $result['replayed'],
            'data'    => $result,
        ]);
    }
}
