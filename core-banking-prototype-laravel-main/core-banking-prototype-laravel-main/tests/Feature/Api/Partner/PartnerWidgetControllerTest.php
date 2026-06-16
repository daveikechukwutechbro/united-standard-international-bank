<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Partner;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerBranding;
use Tests\TestCase;

class PartnerWidgetControllerTest extends TestCase
{
    private FinancialInstitutionPartner $partner;

    private string $clientSecret = 'test_secret_123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = $this->createPartner();

        PartnerBranding::create([
            'partner_id'       => $this->partner->id,
            'primary_color'    => '#1a73e8',
            'secondary_color'  => '#5f6368',
            'text_color'       => '#202124',
            'background_color' => '#ffffff',
            'company_name'     => 'Test Partner',
        ]);
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

    public function test_get_available_widgets(): void
    {
        $response = $this->getJson('/api/partner/v1/widgets', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['payment', 'checkout']]);
    }

    public function test_generate_embed_code(): void
    {
        $response = $this->postJson('/api/partner/v1/widgets/payment/embed', [], $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.widget_type', 'payment');
    }

    public function test_generate_embed_code_invalid_type(): void
    {
        $response = $this->postJson('/api/partner/v1/widgets/nonexistent/embed', [], $this->partnerHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_preview_widget(): void
    {
        $response = $this->getJson('/api/partner/v1/widgets/payment/preview', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.widget_type', 'payment');
    }

    public function test_embed_code_with_custom_options(): void
    {
        $response = $this->postJson('/api/partner/v1/widgets/checkout/embed', [
            'container_id' => 'my-checkout',
            'width'        => '600px',
        ], $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_widgets_denied_for_starter(): void
    {
        $starterPartner = $this->createPartner([
            'tier'              => 'starter',
            'api_client_id'     => 'starter_client',
            'api_client_secret' => encrypt('starter_secret'),
        ]);

        $response = $this->postJson('/api/partner/v1/widgets/payment/embed', [], [
            'X-Partner-Client-Id'     => 'starter_client',
            'X-Partner-Client-Secret' => 'starter_secret',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
