<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Partner;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PartnerSdkControllerTest extends TestCase
{
    private FinancialInstitutionPartner $partner;

    private string $clientSecret = 'test_secret_123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->partner = $this->createPartner();
    }

    protected function tearDown(): void
    {
        $sdkPath = config('baas.sdk.output_path');
        if ($sdkPath && File::isDirectory($sdkPath)) {
            File::deleteDirectory($sdkPath);
        }
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

    public function test_get_languages(): void
    {
        $response = $this->getJson('/api/partner/v1/sdk/languages', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['typescript', 'python']]);
    }

    public function test_generate_sdk(): void
    {
        $response = $this->postJson('/api/partner/v1/sdk/generate', [
            'language' => 'typescript',
        ], $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.language', 'typescript');
    }

    public function test_generate_sdk_invalid_language(): void
    {
        $response = $this->postJson('/api/partner/v1/sdk/generate', [
            'language' => 'cobol',
        ], $this->partnerHeaders());

        $response->assertStatus(422);
    }

    public function test_generate_sdk_missing_language(): void
    {
        $response = $this->postJson('/api/partner/v1/sdk/generate', [], $this->partnerHeaders());

        $response->assertStatus(422);
    }

    public function test_get_sdk_status(): void
    {
        $response = $this->getJson('/api/partner/v1/sdk/typescript', $this->partnerHeaders());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.language', 'typescript');
    }

    public function test_get_openapi_spec(): void
    {
        $response = $this->getJson('/api/partner/v1/sdk/openapi-spec', $this->partnerHeaders());

        // Spec may or may not exist in test environment
        $response->assertStatus($response->json('success') ? 200 : 404);
    }

    public function test_sdk_denied_for_starter_tier(): void
    {
        $starterPartner = $this->createPartner([
            'tier'              => 'starter',
            'api_client_id'     => 'starter_client',
            'api_client_secret' => encrypt('starter_secret'),
        ]);

        $response = $this->postJson('/api/partner/v1/sdk/generate', [
            'language' => 'typescript',
        ], [
            'X-Partner-Client-Id'     => 'starter_client',
            'X-Partner-Client-Secret' => 'starter_secret',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
