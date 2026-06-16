<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Partner;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerBranding;
use Tests\TestCase;

class PartnerDashboardControllerTest extends TestCase
{
    private FinancialInstitutionPartner $partner;

    private string $clientSecret = 'test_secret_123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = $this->createPartner();
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
            'api_client_id'         => 'test_client_abc',
            'api_client_secret'     => encrypt($this->clientSecret),
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

    private function partnerHeaders(): array
    {
        return [
            'X-Partner-Client-Id'     => 'test_client_abc',
            'X-Partner-Client-Secret' => $this->clientSecret,
        ];
    }

    public function test_get_profile(): void
    {
        $response = $this->getJson('/api/partner/v1/profile', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.institution_name', 'Test Partner')
            ->assertJsonPath('data.tier', 'growth');
    }

    public function test_get_profile_unauthenticated(): void
    {
        $response = $this->getJson('/api/partner/v1/profile');

        $response->assertStatus(401);
    }

    public function test_get_usage(): void
    {
        $response = $this->getJson('/api/partner/v1/usage', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['period_start', 'period_end', 'summary', 'limit'],
            ]);
    }

    public function test_get_usage_history(): void
    {
        $response = $this->getJson('/api/partner/v1/usage/history', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_get_tier(): void
    {
        $response = $this->getJson('/api/partner/v1/tier', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tier', 'growth')
            ->assertJsonPath('data.has_sdk', true)
            ->assertJsonPath('data.has_widgets', true);
    }

    public function test_get_tier_comparison(): void
    {
        $response = $this->getJson('/api/partner/v1/tier/comparison', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_get_branding_null(): void
    {
        $response = $this->getJson('/api/partner/v1/branding', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
    }

    public function test_get_branding_with_data(): void
    {
        PartnerBranding::create([
            'partner_id'       => $this->partner->id,
            'primary_color'    => '#1a73e8',
            'secondary_color'  => '#5f6368',
            'text_color'       => '#202124',
            'background_color' => '#ffffff',
            'company_name'     => 'Test Partner Co',
        ]);

        $response = $this->getJson('/api/partner/v1/branding', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_name', 'Test Partner Co');
    }

    public function test_update_branding(): void
    {
        $response = $this->putJson('/api/partner/v1/branding', [
            'primary_color' => '#ff0000',
            'company_name'  => 'Updated Name',
        ], $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
