<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerInvoice;
use App\Domain\FinancialInstitution\Models\PartnerUsageRecord;
use App\Domain\FinancialInstitution\Services\PartnerBillingService;
use App\Domain\FinancialInstitution\Services\PartnerTierService;
use Mockery;
use Tests\TestCase;

class PartnerBillingServiceTest extends TestCase
{
    private PartnerBillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $tierService = new PartnerTierService();
        $this->service = new PartnerBillingService($tierService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    private function createUsageRecord(FinancialInstitutionPartner $partner, string $date, int $apiCalls): PartnerUsageRecord
    {
        return PartnerUsageRecord::create([
            'uuid'              => fake()->uuid(),
            'partner_id'        => $partner->id,
            'usage_date'        => $date,
            'period_type'       => 'daily',
            'api_calls'         => $apiCalls,
            'api_calls_success' => $apiCalls,
            'api_calls_failed'  => 0,
            'is_billable'       => true,
        ]);
    }

    public function test_generate_invoice_creates_invoice(): void
    {
        $partner = $this->createPartner(['tier' => 'growth']);
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->createUsageRecord($partner, $periodStart->copy()->addDays(5)->toDateString(), 50000);

        $invoice = $this->service->generateInvoice($partner, $periodStart, $periodEnd);

        $this->assertInstanceOf(PartnerInvoice::class, $invoice);
        $this->assertEquals($partner->id, $invoice->partner_id);
        $this->assertEquals('pending', $invoice->status);
        $this->assertEquals('growth', $invoice->tier);
        $this->assertEquals(499.00, (float) $invoice->base_amount_usd);
    }

    public function test_generate_invoice_with_overage(): void
    {
        $partner = $this->createPartner(['tier' => 'starter']); // 10K limit
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->createUsageRecord($partner, $periodStart->copy()->addDays(5)->toDateString(), 15000);

        $invoice = $this->service->generateInvoice($partner, $periodStart, $periodEnd);

        $this->assertEquals(99.00, (float) $invoice->base_amount_usd);
        $this->assertEquals(5000, $invoice->overage_api_calls);
        // 5000 overage at $1.00/1000 = $5.00
        $this->assertEquals(5.00, (float) $invoice->overage_amount_usd);
    }

    public function test_calculate_billing_breakdown_monthly(): void
    {
        $partner = $this->createPartner(['tier' => 'growth', 'billing_cycle' => 'monthly']);
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $breakdown = $this->service->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $this->assertEquals('growth', $breakdown['tier']);
        $this->assertEquals('monthly', $breakdown['billing_cycle']);
        $this->assertEquals(499.00, $breakdown['base_amount']);
        $this->assertEquals(0.0, $breakdown['discount_amount']);
        $this->assertEquals(100000, $breakdown['included_api_calls']);
    }

    public function test_calculate_billing_breakdown_quarterly_with_discount(): void
    {
        $partner = $this->createPartner(['tier' => 'growth', 'billing_cycle' => 'quarterly']);
        $periodStart = now()->subMonths(3)->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $breakdown = $this->service->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $baseAmount = 499.00 * 3;
        $this->assertEquals($baseAmount, $breakdown['base_amount']);
        // 5% quarterly discount
        $expectedDiscount = round($baseAmount * 0.05, 2);
        $this->assertEquals($expectedDiscount, $breakdown['discount_amount']);
        $this->assertEquals(300000, $breakdown['included_api_calls']);
    }

    public function test_calculate_billing_breakdown_annually_with_discount(): void
    {
        $partner = $this->createPartner(['tier' => 'enterprise', 'billing_cycle' => 'annually']);
        $periodStart = now()->subYear()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $breakdown = $this->service->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $baseAmount = 1999.00 * 12;
        $this->assertEquals($baseAmount, $breakdown['base_amount']);
        // 15% annual discount
        $expectedDiscount = round($baseAmount * 0.15, 2);
        $this->assertEquals($expectedDiscount, $breakdown['discount_amount']);
    }

    public function test_calculate_billing_breakdown_with_overage_calls(): void
    {
        $partner = $this->createPartner(['tier' => 'starter', 'billing_cycle' => 'monthly']);
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->createUsageRecord($partner, $periodStart->copy()->addDays(3)->toDateString(), 12000);

        $breakdown = $this->service->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $this->assertEquals(12000, $breakdown['total_api_calls']);
        $this->assertEquals(10000, $breakdown['included_api_calls']);
        $this->assertEquals(2000, $breakdown['overage_api_calls']);
        $this->assertEquals(2.0, $breakdown['overage_amount']); // 2000/1000 * $1.00
    }

    public function test_get_outstanding_balance(): void
    {
        $partner = $this->createPartner();

        PartnerInvoice::create([
            'uuid'                   => fake()->uuid(),
            'partner_id'             => $partner->id,
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
        ]);

        PartnerInvoice::create([
            'uuid'                   => fake()->uuid(),
            'partner_id'             => $partner->id,
            'period_start'           => now()->subMonths(2)->startOfMonth(),
            'period_end'             => now()->subMonths(2)->endOfMonth(),
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
        ]);

        $balance = $this->service->getOutstandingBalance($partner);

        $this->assertEquals(998.00, $balance);
    }

    public function test_apply_billing_cycle_discount_monthly(): void
    {
        $discount = $this->service->applyBillingCycleDiscount(499.00, 'monthly');
        $this->assertEquals(0.0, $discount);
    }

    public function test_apply_billing_cycle_discount_quarterly(): void
    {
        $discount = $this->service->applyBillingCycleDiscount(1497.00, 'quarterly');
        // 5% of 1497 = 74.85
        $this->assertEquals(74.85, $discount);
    }

    public function test_apply_billing_cycle_discount_annually(): void
    {
        $discount = $this->service->applyBillingCycleDiscount(5988.00, 'annually');
        // 15% of 5988 = 898.20
        $this->assertEquals(898.20, $discount);
    }

    public function test_generate_batch_invoices(): void
    {
        $partner1 = $this->createPartner(['next_billing_date' => now()->subDay()->toDateString()]);
        $partner2 = $this->createPartner(['next_billing_date' => null]);
        // Partner 3 has future billing date - should be skipped
        $this->createPartner(['next_billing_date' => now()->addMonth()->toDateString()]);

        $invoices = $this->service->generateBatchInvoices();

        $this->assertCount(2, $invoices);
    }

    public function test_line_items_include_plan_and_overage(): void
    {
        $partner = $this->createPartner(['tier' => 'starter', 'billing_cycle' => 'monthly']);
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->createUsageRecord($partner, $periodStart->copy()->addDays(5)->toDateString(), 13000);

        $breakdown = $this->service->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $this->assertCount(2, $breakdown['line_items']);
        $this->assertStringContainsString('Starter plan', $breakdown['line_items'][0]['description']);
        $this->assertStringContainsString('API overage', $breakdown['line_items'][1]['description']);
    }
}
