<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Services\PartnerTierService;
use App\Domain\FinancialInstitution\Services\PartnerUsageMeteringService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Partner BaaS',
    description: 'Partner Banking-as-a-Service dashboard, billing, SDK, widgets, and marketplace'
)]
class PartnerDashboardController extends Controller
{
    public function __construct(
        private readonly PartnerTierService $tierService,
        private readonly PartnerUsageMeteringService $meteringService,
    ) {
    }

    /**
     * Get partner profile and tier information.
     *
     * GET /api/partner/v1/profile
     */
    #[OA\Get(
        path: '/api/partner/v1/profile',
        operationId: 'partnerGetProfile',
        summary: 'Get partner profile and tier information',
        description: 'Returns the authenticated partner\'s profile including institution name, current tier, status, and rate limits.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Partner profile',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'partner_code', type: 'string', example: 'PARTNER_ABC'),
        new OA\Property(property: 'institution_name', type: 'string', example: 'Acme Bank'),
        new OA\Property(property: 'tier', type: 'string', example: 'growth'),
        new OA\Property(property: 'tier_label', type: 'string', example: 'Growth'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'sandbox_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'production_enabled', type: 'boolean', example: false),
        new OA\Property(property: 'rate_limit', type: 'integer', example: 1000),
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
    public function profile(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);
        $tier = $this->tierService->getPartnerTier($partner);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                 => $partner->id,
                'partner_code'       => $partner->partner_code,
                'institution_name'   => $partner->institution_name,
                'tier'               => $tier->value,
                'tier_label'         => $tier->label(),
                'status'             => $partner->status,
                'sandbox_enabled'    => $partner->sandbox_enabled,
                'production_enabled' => $partner->production_enabled,
                'rate_limit'         => $partner->rate_limit_per_minute,
            ],
        ]);
    }

    /**
     * Get current period usage summary.
     *
     * GET /api/partner/v1/usage
     */
    #[OA\Get(
        path: '/api/partner/v1/usage',
        operationId: 'partnerGetUsage',
        summary: 'Get current period usage summary',
        description: 'Returns API usage summary for the current billing period (month-to-date), including call counts by endpoint and usage limit status.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Usage summary',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'period_start', type: 'string', format: 'date', example: '2025-01-01'),
        new OA\Property(property: 'period_end', type: 'string', format: 'date', example: '2025-01-15'),
        new OA\Property(property: 'summary', type: 'object', description: 'Usage breakdown by endpoint and total'),
        new OA\Property(property: 'limit', type: 'object', description: 'Usage limit status and remaining quota'),
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
    public function usage(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);

        $startDate = now()->startOfMonth();
        $endDate = now();

        $summary = $this->meteringService->getUsageSummary($partner, $startDate, $endDate);
        $limit = $this->meteringService->checkUsageLimit($partner);

        return response()->json([
            'success' => true,
            'data'    => [
                'period_start' => $startDate->toDateString(),
                'period_end'   => $endDate->toDateString(),
                'summary'      => $summary,
                'limit'        => $limit,
            ],
        ]);
    }

    /**
     * Get historical usage records.
     *
     * GET /api/partner/v1/usage/history
     */
    #[OA\Get(
        path: '/api/partner/v1/usage/history',
        operationId: 'partnerGetUsageHistory',
        summary: 'Get historical usage records',
        description: 'Returns historical API usage data for a custom date range. Defaults to the last 30 days if no dates are provided.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]],
        parameters: [
        new OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'Start date for usage history (defaults to 1 month ago)', schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-01')),
        new OA\Parameter(name: 'end_date', in: 'query', required: false, description: 'End date for usage history (defaults to today)', schema: new OA\Schema(type: 'string', format: 'date', example: '2025-01-31')),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Historical usage data',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', description: 'Usage summary for the requested period'),
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
    public function usageHistory(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);

        $startDate = $request->date('start_date') ?? now()->subMonth();
        $endDate = $request->date('end_date') ?? now();

        $summary = $this->meteringService->getUsageSummary($partner, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data'    => $summary,
        ]);
    }

    /**
     * Get tier details.
     *
     * GET /api/partner/v1/tier
     */
    #[OA\Get(
        path: '/api/partner/v1/tier',
        operationId: 'partnerGetTier',
        summary: 'Get current tier details',
        description: 'Returns detailed information about the partner\'s current tier including pricing, API call limits, available features, and access levels.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Tier details',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'tier', type: 'string', example: 'growth'),
        new OA\Property(property: 'label', type: 'string', example: 'Growth'),
        new OA\Property(property: 'monthly_price', type: 'number', example: 299.00),
        new OA\Property(property: 'api_call_limit', type: 'integer', example: 100000),
        new OA\Property(property: 'features', type: 'array', example: ['core_banking', 'payments', 'compliance'], items: new OA\Items(type: 'string')),
        new OA\Property(property: 'has_sdk', type: 'boolean', example: true),
        new OA\Property(property: 'has_widgets', type: 'boolean', example: true),
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
    public function tier(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);
        $tier = $this->tierService->getPartnerTier($partner);

        return response()->json([
            'success' => true,
            'data'    => [
                'tier'           => $tier->value,
                'label'          => $tier->label(),
                'monthly_price'  => $tier->monthlyPrice(),
                'api_call_limit' => $tier->apiCallLimit(),
                'features'       => $tier->features(),
                'has_sdk'        => $tier->hasSdkAccess(),
                'has_widgets'    => $tier->hasWidgets(),
            ],
        ]);
    }

    /**
     * Compare all tiers.
     *
     * GET /api/partner/v1/tier/comparison
     */
    #[OA\Get(
        path: '/api/partner/v1/tier/comparison',
        operationId: 'partnerGetTierComparison',
        summary: 'Compare all available tiers',
        description: 'Returns a comparison of all available partnership tiers, highlighting the partner\'s current tier and the differences in features, pricing, and limits.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Tier comparison',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', description: 'Tier comparison matrix with features, pricing, and limits per tier'),
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
    public function tierComparison(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);
        $currentTier = $this->tierService->getPartnerTier($partner);
        $comparison = $this->tierService->getTierComparison($currentTier);

        return response()->json([
            'success' => true,
            'data'    => $comparison,
        ]);
    }

    /**
     * Get current branding configuration.
     *
     * GET /api/partner/v1/branding
     */
    #[OA\Get(
        path: '/api/partner/v1/branding',
        operationId: 'partnerGetBranding',
        summary: 'Get current branding configuration',
        description: 'Returns the partner\'s current branding configuration used for white-label widgets, including colors, logos, and company information.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Branding configuration',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', nullable: true, properties: [
        new OA\Property(property: 'primary_color', type: 'string', example: '#1E40AF'),
        new OA\Property(property: 'secondary_color', type: 'string', example: '#3B82F6'),
        new OA\Property(property: 'accent_color', type: 'string', example: '#F59E0B'),
        new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'https://example.com/logo.png'),
        new OA\Property(property: 'company_name', type: 'string', example: 'Acme Bank'),
        new OA\Property(property: 'support_email', type: 'string', example: 'support@acmebank.com'),
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
    public function branding(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);
        $branding = $partner->branding;

        return response()->json([
            'success' => true,
            'data'    => $branding ? $branding->getWidgetConfig() : null,
        ]);
    }

    /**
     * Update branding configuration.
     *
     * PUT /api/partner/v1/branding
     */
    #[OA\Put(
        path: '/api/partner/v1/branding',
        operationId: 'partnerUpdateBranding',
        summary: 'Update branding configuration',
        description: 'Updates the partner\'s branding configuration for white-label widgets. All fields are optional; only provided fields will be updated.',
        tags: ['Partner BaaS'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'primary_color', type: 'string', example: '#1E40AF', description: 'Primary brand color (hex, max 7 chars)'),
        new OA\Property(property: 'secondary_color', type: 'string', example: '#3B82F6', description: 'Secondary brand color'),
        new OA\Property(property: 'accent_color', type: 'string', example: '#F59E0B', description: 'Accent color'),
        new OA\Property(property: 'text_color', type: 'string', example: '#111827', description: 'Text color'),
        new OA\Property(property: 'background_color', type: 'string', example: '#FFFFFF', description: 'Background color'),
        new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'https://example.com/logo.png', description: 'Logo URL (max 500 chars)'),
        new OA\Property(property: 'company_name', type: 'string', example: 'Acme Bank', description: 'Company display name (max 255 chars)'),
        new OA\Property(property: 'tagline', type: 'string', nullable: true, example: 'Banking made simple', description: 'Company tagline (max 500 chars)'),
        new OA\Property(property: 'support_email', type: 'string', format: 'email', example: 'support@acmebank.com', description: 'Support email'),
        new OA\Property(property: 'privacy_policy_url', type: 'string', nullable: true, example: 'https://example.com/privacy', description: 'Privacy policy URL'),
        new OA\Property(property: 'terms_of_service_url', type: 'string', nullable: true, example: 'https://example.com/terms', description: 'Terms of service URL'),
        ]))
    )]
    #[OA\Response(
        response: 200,
        description: 'Branding updated',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'primary_color', type: 'string', example: '#1E40AF'),
        new OA\Property(property: 'secondary_color', type: 'string', example: '#3B82F6'),
        new OA\Property(property: 'company_name', type: 'string', example: 'Acme Bank'),
        ]),
        new OA\Property(property: 'message', type: 'string', example: 'Branding updated successfully'),
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
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'object', properties: [
        new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_ERROR'),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        ]),
        ])
    )]
    public function updateBranding(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);

        $validated = $request->validate([
            'primary_color'        => 'sometimes|string|max:7',
            'secondary_color'      => 'sometimes|string|max:7',
            'accent_color'         => 'sometimes|string|max:7',
            'text_color'           => 'sometimes|string|max:7',
            'background_color'     => 'sometimes|string|max:7',
            'logo_url'             => 'sometimes|nullable|url|max:500',
            'company_name'         => 'sometimes|string|max:255',
            'tagline'              => 'sometimes|nullable|string|max:500',
            'support_email'        => 'sometimes|email|max:255',
            'privacy_policy_url'   => 'sometimes|nullable|url|max:500',
            'terms_of_service_url' => 'sometimes|nullable|url|max:500',
        ]);

        $branding = $partner->branding;

        if ($branding) {
            $branding->update($validated);
        } else {
            $branding = $this->tierService->createDefaultBranding($partner);
            $branding->update($validated);
        }

        return response()->json([
            'success' => true,
            'data'    => $branding->fresh()?->getWidgetConfig(),
            'message' => 'Branding updated successfully',
        ]);
    }

    private function getPartner(Request $request): FinancialInstitutionPartner
    {
        return $request->attributes->get('partner');
    }
}
