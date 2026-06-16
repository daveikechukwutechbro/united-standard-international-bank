<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Monitoring\Services\LiveMetricsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class LiveDashboardController extends Controller
{
    public function __construct(
        private readonly LiveMetricsService $metricsService,
    ) {
    }

        #[OA\Get(
            path: '/api/v1/monitoring/live-dashboard',
            operationId: 'getLiveDashboard',
            tags: ['Monitoring'],
            summary: 'Get live platform metrics',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Live metrics data'
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getMetrics(),
        ]);
    }

        #[OA\Get(
            path: '/api/v1/monitoring/live-dashboard/domain-health',
            operationId: 'getDomainHealth',
            tags: ['Monitoring'],
            summary: 'Get domain health metrics',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Domain health data'
    )]
    public function domainHealth(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getDomainHealth(),
        ]);
    }

        #[OA\Get(
            path: '/api/v1/monitoring/live-dashboard/event-throughput',
            operationId: 'getEventThroughput',
            tags: ['Monitoring'],
            summary: 'Get event throughput metrics',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Event throughput data'
    )]
    public function eventThroughput(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getEventThroughput(),
        ]);
    }

        #[OA\Get(
            path: '/api/v1/monitoring/live-dashboard/stream-status',
            operationId: 'getStreamStatus',
            tags: ['Monitoring'],
            summary: 'Get event stream status',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Stream status data'
    )]
    public function streamStatus(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getStreamStatus(),
        ]);
    }

        #[OA\Get(
            path: '/api/v1/monitoring/live-dashboard/projector-lag',
            operationId: 'getProjectorLag',
            tags: ['Monitoring'],
            summary: 'Get projector lag metrics',
            security: [['sanctum' => []]]
        )]
    #[OA\Response(
        response: 200,
        description: 'Projector lag data'
    )]
    public function projectorLag(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsService->getProjectorLag(),
        ]);
    }
}
