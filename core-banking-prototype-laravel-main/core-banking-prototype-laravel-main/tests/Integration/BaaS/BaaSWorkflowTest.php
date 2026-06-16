<?php

declare(strict_types=1);

namespace Tests\Integration\BaaS;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerBranding;
use App\Domain\FinancialInstitution\Services\EmbeddableWidgetService;
use App\Domain\FinancialInstitution\Services\PartnerBillingService;
use App\Domain\FinancialInstitution\Services\PartnerMarketplaceService;
use App\Domain\FinancialInstitution\Services\PartnerTierService;
use App\Domain\FinancialInstitution\Services\PartnerUsageMeteringService;
use App\Domain\FinancialInstitution\Services\SdkGeneratorService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * End-to-end workflow tests for the BaaS platform.
 */
class BaaSWorkflowTest extends TestCase
{
    private string $clientSecret = 'test_secret_123';

    private function createPartnerApplication(): FinancialInstitutionApplication
    {
        return FinancialInstitutionApplication::create([
            'application_number'       => 'FIA-2026-' . fake()->unique()->numerify('#####'),
            'institution_name'         => 'Workflow Test Partner',
            'legal_name'               => 'Workflow Test Partner Ltd',
            'registration_number'      => 'REG-WF-123',
            'tax_id'                   => 'TAX-WF-123',
            'country'                  => 'US',
            'institution_type'         => 'fintech',
            'years_in_operation'       => 3,
            'contact_name'             => 'Jane Doe',
            'contact_email'            => 'jane@workflow.test',
            'contact_phone'            => '+1234567890',
            'contact_position'         => 'CTO',
            'headquarters_address'     => '456 Test Ave',
            'headquarters_city'        => 'San Francisco',
            'headquarters_postal_code' => '94105',
            'headquarters_country'     => 'US',
            'business_description'     => 'Workflow test fintech partner',
            'target_markets'           => ['US'],
            'product_offerings'        => ['payments', 'transfers'],
            'required_currencies'      => ['USD'],
            'integration_requirements' => ['api', 'widgets'],
            'status'                   => 'approved',
        ]);
    }

    private function createPartner(array $attributes = []): FinancialInstitutionPartner
    {
        $application = $this->createPartnerApplication();

        return FinancialInstitutionPartner::create(array_merge([
            'application_id'        => $application->id,
            'partner_code'          => 'WFT-' . fake()->unique()->numerify('####'),
            'institution_name'      => 'Workflow Test Partner',
            'legal_name'            => 'Workflow Test Partner Ltd',
            'institution_type'      => 'fintech',
            'country'               => 'US',
            'status'                => 'active',
            'tier'                  => 'growth',
            'billing_cycle'         => 'monthly',
            'api_client_id'         => 'wft_client_' . fake()->unique()->numerify('####'),
            'api_client_secret'     => encrypt($this->clientSecret),
            'webhook_secret'        => encrypt('webhook_secret_123'),
            'sandbox_enabled'       => true,
            'production_enabled'    => false,
            'rate_limit_per_minute' => 300,
            'fee_structure'         => ['base' => 0],
            'risk_rating'           => 'low',
            'risk_score'            => 10.00,
            'primary_contact'       => ['name' => 'Jane', 'email' => 'jane@workflow.test'],
        ], $attributes));
    }

    /**
     * Test the full BaaS lifecycle: partner setup → usage → billing → SDK → widgets → marketplace.
     */
    public function test_full_baas_lifecycle(): void
    {
        $tierService = new PartnerTierService();
        $meteringService = new PartnerUsageMeteringService($tierService);
        $billingService = new PartnerBillingService($tierService);
        $sdkService = new SdkGeneratorService($tierService);
        $widgetService = new EmbeddableWidgetService($tierService);
        $marketplaceService = new PartnerMarketplaceService();

        // Step 1: Create partner with Growth tier
        $partner = $this->createPartner(['tier' => 'growth']);
        $tier = $tierService->getPartnerTier($partner);
        $this->assertEquals('growth', $tier->value);
        $this->assertTrue($tier->hasSdkAccess());
        $this->assertTrue($tier->hasWidgets());

        // Step 2: Record API usage
        $meteringService->recordApiCall($partner, '/api/accounts', true, 120);
        $meteringService->recordApiCall($partner, '/api/transfers', true, 200);
        $meteringService->recordApiCall($partner, '/api/accounts', false, 500);

        $usageLimit = $meteringService->checkUsageLimit($partner);
        $this->assertFalse($usageLimit['exceeded']);
        $this->assertEquals(3, $usageLimit['current']);

        // Step 3: Generate billing breakdown
        $periodStart = now()->startOfMonth();
        $periodEnd = now();
        $breakdown = $billingService->calculateBillingBreakdown($partner, $periodStart, $periodEnd);
        $this->assertEquals('growth', $breakdown['tier']);
        $this->assertEquals(499.00, $breakdown['base_amount']);
        $this->assertEquals(0, $breakdown['overage_api_calls']);

        // Step 4: Generate SDK
        $sdkResult = $sdkService->generate($partner, 'typescript');
        $this->assertTrue($sdkResult['success']);
        $this->assertEquals('typescript', $sdkResult['language']);

        // Clean up SDK files
        $sdkPath = config('baas.sdk.output_path');
        if ($sdkPath && File::isDirectory($sdkPath)) {
            File::deleteDirectory($sdkPath);
        }

        // Step 5: Setup branding for widgets
        PartnerBranding::create([
            'partner_id'       => $partner->id,
            'primary_color'    => '#2196F3',
            'secondary_color'  => '#FF9800',
            'text_color'       => '#212121',
            'background_color' => '#FAFAFA',
            'company_name'     => 'Workflow Test Co',
        ]);

        // Step 6: Generate widget embed code
        $partner->refresh();
        $embedResult = $widgetService->generateEmbedCode($partner, 'payment');
        $this->assertTrue($embedResult['success']);
        $this->assertStringContainsString('finaegis-payment.js', (string) $embedResult['html']);

        // Step 7: Enable marketplace integration
        $integrationResult = $marketplaceService->enableIntegration(
            $partner,
            'payment_processors',
            'stripe',
            ['api_key' => 'sk_test_workflow'],
        );
        $this->assertTrue($integrationResult['success']);

        // Step 8: Check integration health
        $health = $marketplaceService->getIntegrationHealth($partner);
        $this->assertEquals(1, $health['total']);
        $this->assertEquals(1, $health['active']);
        $this->assertGreaterThanOrEqual(0, $health['health_score']);
    }

    /**
     * Test starter tier restrictions across all BaaS services.
     */
    public function test_starter_tier_restrictions(): void
    {
        $tierService = new PartnerTierService();
        $sdkService = new SdkGeneratorService($tierService);
        $widgetService = new EmbeddableWidgetService($tierService);

        $partner = $this->createPartner(['tier' => 'starter']);

        // SDK denied
        $sdkResult = $sdkService->generate($partner, 'typescript');
        $this->assertFalse($sdkResult['success']);
        $this->assertStringContainsString('Growth or Enterprise', $sdkResult['message']);

        // Widgets denied
        $widgetResult = $widgetService->generateEmbedCode($partner, 'payment');
        $this->assertFalse($widgetResult['success']);
        $this->assertStringContainsString('Growth or Enterprise', $widgetResult['message']);
    }

    /**
     * Test partner API authentication flow end-to-end.
     */
    public function test_partner_api_authentication_flow(): void
    {
        $partner = $this->createPartner([
            'api_client_id'     => 'auth_flow_client',
            'api_client_secret' => encrypt($this->clientSecret),
        ]);

        // Authenticated request succeeds
        $response = $this->getJson('/api/partner/v1/profile', [
            'X-Partner-Client-Id'     => 'auth_flow_client',
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);
        $response->assertOk();

        // Missing credentials fails
        $response = $this->getJson('/api/partner/v1/profile');
        $response->assertStatus(401);

        // Wrong credentials fails
        $response = $this->getJson('/api/partner/v1/profile', [
            'X-Partner-Client-Id'     => 'auth_flow_client',
            'X-Partner-Client-Secret' => 'wrong_secret',
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test billing with overage across the API and service layer.
     */
    public function test_billing_overage_workflow(): void
    {
        $tierService = new PartnerTierService();
        $meteringService = new PartnerUsageMeteringService($tierService);
        $billingService = new PartnerBillingService($tierService);

        $partner = $this->createPartner(['tier' => 'starter']); // 10K limit

        // Record usage exceeding the starter tier limit
        $record = $meteringService->getOrCreateTodayRecord($partner);
        $record->incrementApiCalls(15000, true, '/api/accounts');

        // Generate invoice
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $invoice = $billingService->generateInvoice($partner, $periodStart, $periodEnd);

        $this->assertEquals('pending', $invoice->status);
        $this->assertEquals(5000, $invoice->overage_api_calls);
        $this->assertGreaterThan(0, (float) $invoice->overage_amount_usd);
    }

    /**
     * Test the marketplace integration lifecycle.
     */
    public function test_marketplace_integration_lifecycle(): void
    {
        $marketplaceService = new PartnerMarketplaceService();
        $partner = $this->createPartner();

        // Enable
        $enableResult = $marketplaceService->enableIntegration($partner, 'analytics', 'mixpanel', ['project_token' => 'test']);
        $this->assertTrue($enableResult['success']);
        $integrationId = $enableResult['integration']->id;

        // List
        $integrations = $marketplaceService->getPartnerIntegrations($partner);
        $this->assertCount(1, $integrations);

        // Test connection
        $testResult = $marketplaceService->testConnection($partner, $integrationId);
        $this->assertTrue($testResult['success']);

        // Disable
        $disableResult = $marketplaceService->disableIntegration($partner, $integrationId);
        $this->assertTrue($disableResult['success']);

        // Verify disabled
        $integrations = $marketplaceService->getPartnerIntegrations($partner);
        $this->assertCount(0, $integrations);

        // Re-enable
        $reEnableResult = $marketplaceService->enableIntegration($partner, 'analytics', 'mixpanel');
        $this->assertTrue($reEnableResult['success']);
    }
}
