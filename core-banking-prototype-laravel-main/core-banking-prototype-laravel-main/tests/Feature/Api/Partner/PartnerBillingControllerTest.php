<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Partner;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerInvoice;
use Tests\TestCase;

class PartnerBillingControllerTest extends TestCase
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

    private function createInvoice(array $attributes = []): PartnerInvoice
    {
        return PartnerInvoice::create(array_merge([
            'uuid'                   => fake()->uuid(),
            'partner_id'             => $this->partner->id,
            'period_start'           => now()->subMonth()->startOfMonth(),
            'period_end'             => now()->subMonth()->endOfMonth(),
            'billing_cycle'          => 'monthly',
            'status'                 => 'pending',
            'tier'                   => 'growth',
            'base_amount_usd'        => 499.00,
            'discount_amount_usd'    => 0,
            'total_api_calls'        => 50000,
            'included_api_calls'     => 100000,
            'overage_api_calls'      => 0,
            'overage_amount_usd'     => 0,
            'line_items'             => [],
            'additional_charges_usd' => 0,
            'subtotal_usd'           => 499.00,
            'tax_amount_usd'         => 0,
            'tax_rate'               => 0,
            'total_amount_usd'       => 499.00,
            'display_currency'       => 'USD',
            'exchange_rate'          => 1.0,
            'total_amount_display'   => 499.00,
            'due_date'               => now()->addDays(30),
        ], $attributes));
    }

    public function test_list_invoices(): void
    {
        $this->createInvoice();

        $response = $this->getJson('/api/partner/v1/billing/invoices', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_list_invoices_empty(): void
    {
        $response = $this->getJson('/api/partner/v1/billing/invoices', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_get_invoice(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->getJson("/api/partner/v1/billing/invoices/{$invoice->id}", $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_get_invoice_not_found(): void
    {
        $response = $this->getJson('/api/partner/v1/billing/invoices/999999', $this->partnerHeaders());

        $response->assertStatus(404);
    }

    public function test_get_outstanding_balance(): void
    {
        $this->createInvoice();

        $response = $this->getJson('/api/partner/v1/billing/outstanding', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.outstanding_balance_usd', 499)
            ->assertJsonPath('data.currency', 'USD');
    }

    public function test_get_billing_breakdown(): void
    {
        $response = $this->getJson('/api/partner/v1/billing/breakdown', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tier', 'growth')
            ->assertJsonPath('data.billing_cycle', 'monthly');
    }
}
