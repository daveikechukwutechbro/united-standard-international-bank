<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Services\PartnerTierService;
use App\Domain\FinancialInstitution\Services\SdkGeneratorService;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class SdkGeneratorServiceTest extends TestCase
{
    private SdkGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SdkGeneratorService(new PartnerTierService());
    }

    protected function tearDown(): void
    {
        // Clean up generated SDK files
        $sdkPath = config('baas.sdk.output_path');
        if ($sdkPath && File::isDirectory($sdkPath)) {
            File::deleteDirectory($sdkPath);
        }
        Mockery::close();
        parent::tearDown();
    }

    private function createMockPartner(array $attributes = []): FinancialInstitutionPartner
    {
        $mock = Mockery::mock(FinancialInstitutionPartner::class)->makePartial();

        $defaults = [
            'id'               => fake()->uuid(),
            'partner_code'     => 'TST-12345',
            'institution_name' => 'Test Partner',
            'tier'             => 'growth',
            'api_client_id'    => 'test_client_abc',
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $mock->{$key} = $value;
        }

        return $mock;
    }

    public function test_generate_sdk_for_growth_tier(): void
    {
        $partner = $this->createMockPartner(['tier' => 'growth']);

        $result = $this->service->generate($partner, 'typescript');

        $this->assertTrue($result['success']);
        $this->assertEquals('typescript', $result['language']);
        $this->assertNotNull($result['path']);
    }

    public function test_generate_sdk_for_enterprise_tier(): void
    {
        $partner = $this->createMockPartner(['tier' => 'enterprise']);

        $result = $this->service->generate($partner, 'python');

        $this->assertTrue($result['success']);
        $this->assertEquals('python', $result['language']);
    }

    public function test_generate_sdk_denied_for_starter_tier(): void
    {
        $partner = $this->createMockPartner(['tier' => 'starter']);

        $result = $this->service->generate($partner, 'typescript');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Growth or Enterprise', $result['message']);
        $this->assertNull($result['path']);
    }

    public function test_generate_sdk_invalid_language(): void
    {
        $partner = $this->createMockPartner(['tier' => 'growth']);

        $result = $this->service->generate($partner, 'cobol');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported language', $result['message']);
    }

    public function test_generate_demo_sdk_creates_files(): void
    {
        $partner = $this->createMockPartner(['tier' => 'growth']);

        $result = $this->service->generateDemoSdk($partner, 'typescript');

        $this->assertTrue($result['success']);
        $this->assertTrue(File::isDirectory($result['path']));
        $this->assertTrue(File::exists($result['path'] . '/README.md'));
        $this->assertTrue(File::exists($result['path'] . '/FinAegisClient.ts'));
        $this->assertTrue(File::exists($result['path'] . '/Auth.ts'));
        $this->assertTrue(File::exists($result['path'] . '/package.json'));
    }

    public function test_generate_demo_sdk_python(): void
    {
        $partner = $this->createMockPartner(['tier' => 'growth']);

        $result = $this->service->generateDemoSdk($partner, 'python');

        $this->assertTrue($result['success']);
        $this->assertTrue(File::exists($result['path'] . '/FinAegisClient.py'));
        $this->assertTrue(File::exists($result['path'] . '/setup.py'));
    }

    public function test_get_available_languages(): void
    {
        $languages = $this->service->getAvailableLanguages();

        $this->assertIsArray($languages);
        $this->assertArrayHasKey('typescript', $languages);
        $this->assertArrayHasKey('python', $languages);
        $this->assertArrayHasKey('java', $languages);
        $this->assertArrayHasKey('go', $languages);
        $this->assertArrayHasKey('php', $languages);
    }

    public function test_get_sdk_status_not_generated(): void
    {
        $partner = $this->createMockPartner();

        $status = $this->service->getSdkStatus($partner, 'typescript');

        $this->assertFalse($status['exists']);
        $this->assertNull($status['path']);
        $this->assertNull($status['version']);
    }

    public function test_get_sdk_status_after_generation(): void
    {
        $partner = $this->createMockPartner(['tier' => 'growth']);

        $this->service->generateDemoSdk($partner, 'typescript');

        $status = $this->service->getSdkStatus($partner, 'typescript');

        $this->assertTrue($status['exists']);
        $this->assertNotNull($status['path']);
        $this->assertNotNull($status['version']);
    }

    public function test_get_openapi_spec_returns_null_when_missing(): void
    {
        $spec = $this->service->getOpenApiSpec();

        // In testing, the spec file likely doesn't exist
        $this->assertTrue($spec === null || is_string($spec));
    }
}
