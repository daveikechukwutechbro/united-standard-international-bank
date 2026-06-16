<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerUsageRecord;
use App\Domain\FinancialInstitution\Services\PartnerTierService;
use App\Domain\FinancialInstitution\Services\PartnerUsageMeteringService;
use Mockery;
use Tests\TestCase;

class PartnerUsageMeteringServiceTest extends TestCase
{
    private PartnerUsageMeteringService $service;

    private PartnerTierService $tierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tierService = new PartnerTierService();
        $this->service = new PartnerUsageMeteringService($this->tierService);
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

    public function test_record_api_call_creates_daily_record(): void
    {
        $partner = $this->createPartner();

        $this->service->recordApiCall($partner, '/api/accounts', true, 150);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(now()->toDateString(), $record->usage_date->toDateString());
        $this->assertEquals(1, $record->api_calls);
        $this->assertEquals(1, $record->api_calls_success);
        $this->assertEquals(0, $record->api_calls_failed);
    }

    public function test_record_api_call_increments_existing_record(): void
    {
        $partner = $this->createPartner();

        $this->service->recordApiCall($partner, '/api/accounts', true, 100);
        $this->service->recordApiCall($partner, '/api/accounts', true, 200);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(2, $record->api_calls);
        $this->assertEquals(2, $record->api_calls_success);
    }

    public function test_record_failed_api_call(): void
    {
        $partner = $this->createPartner();

        $this->service->recordApiCall($partner, '/api/transfers', false);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(1, $record->api_calls);
        $this->assertEquals(0, $record->api_calls_success);
        $this->assertEquals(1, $record->api_calls_failed);
    }

    public function test_record_api_call_tracks_endpoint_breakdown(): void
    {
        $partner = $this->createPartner();

        $this->service->recordApiCall($partner, '/api/accounts', true);
        $this->service->recordApiCall($partner, '/api/accounts', true);
        $this->service->recordApiCall($partner, '/api/transfers', true);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $breakdown = $record->endpoint_breakdown;
        $this->assertEquals(2, $breakdown['/api/accounts']);
        $this->assertEquals(1, $breakdown['/api/transfers']);
    }

    public function test_record_api_call_updates_response_time(): void
    {
        $partner = $this->createPartner();

        $this->service->recordApiCall($partner, '/api/accounts', true, 100);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(100.0, (float) $record->avg_response_time_ms);
        $this->assertEquals(100, $record->p99_response_time_ms);
    }

    public function test_record_widget_load(): void
    {
        $partner = $this->createPartner();

        $this->service->recordWidgetLoad($partner, 'payment', false);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(1, $record->widget_loads);
        $this->assertEquals(0, $record->widget_conversions);
    }

    public function test_record_widget_load_with_conversion(): void
    {
        $partner = $this->createPartner();

        $this->service->recordWidgetLoad($partner, 'checkout', true);

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(1, $record->widget_loads);
        $this->assertEquals(1, $record->widget_conversions);
    }

    public function test_record_sdk_download(): void
    {
        $partner = $this->createPartner();

        $this->service->recordSdkDownload($partner, 'typescript');
        $this->service->recordSdkDownload($partner, 'python');

        $record = PartnerUsageRecord::where('partner_id', $partner->id)->first();
        $this->assertEquals(2, $record->sdk_downloads);
    }

    public function test_get_or_create_today_record(): void
    {
        $partner = $this->createPartner();

        $record1 = $this->service->getOrCreateTodayRecord($partner);
        $record2 = $this->service->getOrCreateTodayRecord($partner);

        $this->assertEquals($record1->id, $record2->id);
        $this->assertEquals('daily', $record1->period_type);
        $this->assertTrue($record1->is_billable);
    }

    public function test_get_usage_summary(): void
    {
        $partner = $this->createPartner();

        // Create some usage records
        PartnerUsageRecord::create([
            'uuid'               => fake()->uuid(),
            'partner_id'         => $partner->id,
            'usage_date'         => now()->subDays(2)->toDateString(),
            'period_type'        => 'daily',
            'api_calls'          => 500,
            'api_calls_success'  => 480,
            'api_calls_failed'   => 20,
            'widget_loads'       => 10,
            'widget_conversions' => 3,
            'sdk_downloads'      => 1,
            'is_billable'        => true,
        ]);

        PartnerUsageRecord::create([
            'uuid'               => fake()->uuid(),
            'partner_id'         => $partner->id,
            'usage_date'         => now()->subDays(1)->toDateString(),
            'period_type'        => 'daily',
            'api_calls'          => 300,
            'api_calls_success'  => 290,
            'api_calls_failed'   => 10,
            'widget_loads'       => 5,
            'widget_conversions' => 2,
            'sdk_downloads'      => 0,
            'is_billable'        => true,
        ]);

        $summary = $this->service->getUsageSummary(
            $partner,
            now()->subDays(7),
            now(),
        );

        $this->assertEquals($partner->id, $summary['partner_id']);
        $this->assertEquals(800, $summary['api_calls']['total']);
        $this->assertEquals(770, $summary['api_calls']['successful']);
        $this->assertEquals(30, $summary['api_calls']['failed']);
        $this->assertEquals(96.25, $summary['api_calls']['success_rate']);
        $this->assertEquals(15, $summary['widget_loads']);
        $this->assertEquals(5, $summary['widget_conversions']);
        $this->assertEquals(1, $summary['sdk_downloads']);
        $this->assertEquals(2, $summary['daily_records']);
    }

    public function test_check_usage_limit_not_exceeded(): void
    {
        $partner = $this->createPartner(['tier' => 'growth']); // 100K limit

        PartnerUsageRecord::create([
            'uuid'              => fake()->uuid(),
            'partner_id'        => $partner->id,
            'usage_date'        => now()->toDateString(),
            'period_type'       => 'daily',
            'api_calls'         => 5000,
            'api_calls_success' => 5000,
            'api_calls_failed'  => 0,
            'is_billable'       => true,
        ]);

        $result = $this->service->checkUsageLimit($partner);

        $this->assertFalse($result['exceeded']);
        $this->assertEquals(5000, $result['current']);
        $this->assertEquals(100000, $result['limit']);
        $this->assertEquals(5.0, $result['percentage']);
    }

    public function test_check_usage_limit_exceeded(): void
    {
        $partner = $this->createPartner(['tier' => 'starter']); // 10K limit

        PartnerUsageRecord::create([
            'uuid'              => fake()->uuid(),
            'partner_id'        => $partner->id,
            'usage_date'        => now()->toDateString(),
            'period_type'       => 'daily',
            'api_calls'         => 15000,
            'api_calls_success' => 14000,
            'api_calls_failed'  => 1000,
            'is_billable'       => true,
        ]);

        $result = $this->service->checkUsageLimit($partner);

        $this->assertTrue($result['exceeded']);
        $this->assertEquals(15000, $result['current']);
        $this->assertEquals(10000, $result['limit']);
        $this->assertEquals(150.0, $result['percentage']);
    }
}
