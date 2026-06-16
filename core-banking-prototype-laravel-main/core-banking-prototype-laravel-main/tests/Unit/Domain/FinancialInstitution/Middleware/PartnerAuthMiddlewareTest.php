<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Middleware;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionApplication;
use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Http\Middleware\PartnerAuthMiddleware;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class PartnerAuthMiddlewareTest extends TestCase
{
    private PartnerAuthMiddleware $middleware;

    private string $clientSecret = 'test_secret_abc123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PartnerAuthMiddleware();
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

    private function createRequest(array $headers = []): Request
    {
        $request = Request::create('/api/partner/v1/profile', 'GET');

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }

    private function passThrough(): Closure
    {
        return fn (Request $request) => new JsonResponse(['status' => 'ok']);
    }

    public function test_missing_credentials_returns_401(): void
    {
        $request = $this->createRequest();
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function test_missing_client_secret_returns_401(): void
    {
        $request = $this->createRequest([
            'X-Partner-Client-Id' => 'some_id',
        ]);
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_invalid_client_id_returns_401(): void
    {
        $request = $this->createRequest([
            'X-Partner-Client-Id'     => 'nonexistent_id',
            'X-Partner-Client-Secret' => 'some_secret',
        ]);
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_invalid_client_secret_returns_401(): void
    {
        $partner = $this->createPartner();

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => 'wrong_secret',
        ]);

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_inactive_partner_returns_403(): void
    {
        $partner = $this->createPartner(['status' => 'suspended']);

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('not active', $data['message']);
    }

    public function test_ip_not_allowed_returns_403(): void
    {
        $partner = $this->createPartner([
            'allowed_ip_addresses' => ['10.0.0.1', '10.0.0.2'],
        ]);

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('IP address', $data['message']);
    }

    public function test_valid_credentials_passes_through(): void
    {
        $partner = $this->createPartner();

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_valid_credentials_binds_partner_to_request(): void
    {
        $partner = $this->createPartner();

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);

        $this->middleware->handle($request, function (Request $req) use ($partner) {
            $boundPartner = $req->attributes->get('partner');
            $this->assertInstanceOf(FinancialInstitutionPartner::class, $boundPartner);
            $this->assertEquals($partner->id, $boundPartner->id);

            return new JsonResponse(['status' => 'ok']);
        });
    }

    public function test_empty_ip_allowlist_allows_all_ips(): void
    {
        $partner = $this->createPartner([
            'allowed_ip_addresses' => [],
        ]);

        $request = $this->createRequest([
            'X-Partner-Client-Id'     => $partner->api_client_id,
            'X-Partner-Client-Secret' => $this->clientSecret,
        ]);

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }
}
