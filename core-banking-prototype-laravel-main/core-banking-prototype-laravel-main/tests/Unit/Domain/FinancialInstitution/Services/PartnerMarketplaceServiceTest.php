<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Services\PartnerMarketplaceService;
use Tests\TestCase;

class PartnerMarketplaceServiceTest extends TestCase
{
    private PartnerMarketplaceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PartnerMarketplaceService();
    }

    private function createPartnerApplication(): FinancialInstitutionApplication
    {
        return FinancialInstitutionApplication::create([
            'application_number'       => 'FIA-2026-' . fake()->unique()->numerify('#####'),
            'institution_name'         => 'Test Partner',
            'legal_name'               => 'Test Partner Ltd',
            'registration_number'      => 'REG-123456',
            'tax_id'                   => 'TAX-123456',
            'country'                  => 'US',
            'institution_type'         => 'fintech',
            'years_in_operation'       => 5,
            'contact_name'             => 'John Doe',
            'contact_email'            => 'john@test.com',
            'contact_phone'            => '+1234567890',
            'contact_position'         => 'CTO',
            'headquarters_address'     => '123 Test St',
            'headquarters_city'        => 'New York',
            'headquarters_postal_code' => '10001',
            'headquarters_country'     => 'US',
            'business_description'     => 'Test fintech partner',
            'target_markets'           => ['US', 'EU'],
            'product_offerings'        => ['payments'],
            'required_currencies'      => ['USD'],
            'integration_requirements' => ['api'],
            'status'                   => 'approved',
        ]);
    }

    private function createPartner(array $attributes = []): FinancialInstitutionPartner
    {
        $application = $this->createPartnerApplication();

        return FinancialInstitutionPartner::create(array_merge([
            'application_id'        => $application->id,
            'partner_code'          => 'TST-' . fake()->unique()->numerify('####'),
            'institution_name'      => 'Test Partner',
            'legal_name'            => 'Test Partner Ltd',
            'institution_type'      => 'fintech',
            'country'               => 'US',
            'status'                => 'active',
            'tier'                  => 'growth',
            'billing_cycle'         => 'monthly',
            'api_client_id'         => 'test_client_' . fake()->unique()->numerify('####'),
            'api_client_secret'     => encrypt('test_secret_123'),
            'webhook_secret'        => encrypt('webhook_secret_123'),
            'sandbox_enabled'       => true,
            'production_enabled'    => false,
            'rate_limit_per_minute' => 300,
            'fee_structure'         => ['base' => 0],
            'risk_rating'           => 'low',
            'risk_score'            => 10.00,
            'primary_contact'       => ['name' => 'Test', 'email' => 'test@example.com'],
        ], $attributes));
    }

    public function test_list_available_integrations(): void
    {
        $integrations = $this->service->listAvailableIntegrations();

        $this->assertIsArray($integrations);
        $this->assertArrayHasKey('payment_processors', $integrations);
        $this->assertArrayHasKey('identity_providers', $integrations);
        $this->assertArrayHasKey('kyc_providers', $integrations);
        $this->assertArrayHasKey('accounting', $integrations);
        $this->assertArrayHasKey('analytics', $integrations);
    }

    public function test_enable_integration_success(): void
    {
        $partner = $this->createPartner();

        $result = $this->service->enableIntegration($partner, 'payment_processors', 'stripe', [
            'api_key' => 'sk_test_123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['integration']);
        $this->assertEquals('stripe', $result['integration']->provider);
        $this->assertEquals('active', $result['integration']->status);
    }

    public function test_enable_integration_invalid_category(): void
    {
        $partner = $this->createPartner();

        $result = $this->service->enableIntegration($partner, 'nonexistent_category', 'stripe');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown integration category', $result['message']);
    }

    public function test_enable_integration_invalid_provider(): void
    {
        $partner = $this->createPartner();

        $result = $this->service->enableIntegration($partner, 'payment_processors', 'nonexistent_provider');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not available', $result['message']);
    }

    public function test_enable_integration_already_active(): void
    {
        $partner = $this->createPartner();

        $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $result = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already active', $result['message']);
    }

    public function test_enable_integration_reactivates_disabled(): void
    {
        $partner = $this->createPartner();

        $enableResult = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $this->service->disableIntegration($partner, $enableResult['integration']->id);
        $result = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');

        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['integration']->status);
    }

    public function test_disable_integration(): void
    {
        $partner = $this->createPartner();

        $enableResult = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $result = $this->service->disableIntegration($partner, $enableResult['integration']->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
    }

    public function test_disable_integration_not_found(): void
    {
        $partner = $this->createPartner();

        $result = $this->service->disableIntegration($partner, 999999);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function test_get_partner_integrations(): void
    {
        $partner = $this->createPartner();

        $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $this->service->enableIntegration($partner, 'analytics', 'mixpanel');

        $integrations = $this->service->getPartnerIntegrations($partner);

        $this->assertCount(2, $integrations);
    }

    public function test_test_connection(): void
    {
        $partner = $this->createPartner();

        $enableResult = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $result = $this->service->testConnection($partner, $enableResult['integration']->id);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['latency_ms']);
        $this->assertStringContainsString('stripe', $result['message']);
    }

    public function test_test_connection_not_found(): void
    {
        $partner = $this->createPartner();

        $result = $this->service->testConnection($partner, 999999);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['latency_ms']);
    }

    public function test_get_integration_health(): void
    {
        $partner = $this->createPartner();

        $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $this->service->enableIntegration($partner, 'analytics', 'mixpanel');

        $health = $this->service->getIntegrationHealth($partner);

        $this->assertEquals(2, $health['total']);
        $this->assertEquals(2, $health['active']);
        $this->assertEquals(0, $health['errored']);
        $this->assertEquals(100.0, $health['health_score']);
    }

    public function test_get_integration_health_with_errors(): void
    {
        $partner = $this->createPartner();

        $enableResult = $this->service->enableIntegration($partner, 'payment_processors', 'stripe');
        $enableResult['integration']->recordError('Connection timeout');

        $this->service->enableIntegration($partner, 'analytics', 'mixpanel');

        $health = $this->service->getIntegrationHealth($partner);

        $this->assertEquals(2, $health['total']);
        $this->assertEquals(2, $health['active']);
        $this->assertEquals(1, $health['errored']);
        $this->assertEquals(50.0, $health['health_score']);
    }

    public function test_get_integration_health_no_integrations(): void
    {
        $partner = $this->createPartner();

        $health = $this->service->getIntegrationHealth($partner);

        $this->assertEquals(0, $health['total']);
        $this->assertEquals(100.0, $health['health_score']);
    }
}
