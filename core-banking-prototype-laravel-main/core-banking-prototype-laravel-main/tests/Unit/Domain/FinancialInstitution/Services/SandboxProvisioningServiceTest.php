<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Services\SandboxProvisioningService;
use Tests\TestCase;

class SandboxProvisioningServiceTest extends TestCase
{
    private SandboxProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SandboxProvisioningService();
    }

    public function test_create_sandbox_returns_expected_keys(): void
    {
        $result = $this->service->createSandbox('partner-123');

        $this->assertArrayHasKey('sandbox_id', $result);
        $this->assertArrayHasKey('api_key', $result);
        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('seed_counts', $result);
    }

    public function test_create_sandbox_generates_unique_ids(): void
    {
        $result1 = $this->service->createSandbox('partner-123');
        $result2 = $this->service->createSandbox('partner-123');

        $this->assertNotEquals($result1['sandbox_id'], $result2['sandbox_id']);
        $this->assertNotEquals($result1['api_key'], $result2['api_key']);
    }

    public function test_create_sandbox_api_key_has_sandbox_prefix(): void
    {
        $result = $this->service->createSandbox('partner-abc');

        $this->assertStringStartsWith('sk_sandbox_', $result['api_key']);
    }

    public function test_create_sandbox_id_has_sandbox_prefix(): void
    {
        $result = $this->service->createSandbox('partner-abc');

        $this->assertStringStartsWith('sandbox-', $result['sandbox_id']);
    }

    public function test_create_sandbox_defaults_to_basic_profile(): void
    {
        $result = $this->service->createSandbox('partner-123');

        $this->assertEquals('basic', $result['profile']);
        $this->assertArrayHasKey('users', $result['seed_counts']);
        $this->assertEquals(5, $result['seed_counts']['users']);
    }

    public function test_create_sandbox_with_full_profile(): void
    {
        $result = $this->service->createSandbox('partner-123', 'full');

        $this->assertEquals('full', $result['profile']);
        $this->assertEquals(20, $result['seed_counts']['users']);
        $this->assertEquals(40, $result['seed_counts']['accounts']);
        $this->assertEquals(200, $result['seed_counts']['transactions']);
        $this->assertEquals(10, $result['seed_counts']['loans']);
        $this->assertEquals(5, $result['seed_counts']['cards']);
        $this->assertEquals(10, $result['seed_counts']['wallets']);
    }

    public function test_create_sandbox_with_payments_profile(): void
    {
        $result = $this->service->createSandbox('partner-123', 'payments');

        $this->assertEquals('payments', $result['profile']);
        $this->assertEquals(10, $result['seed_counts']['users']);
        $this->assertEquals(50, $result['seed_counts']['payment_intents']);
    }

    public function test_create_sandbox_falls_back_to_basic_for_unknown_profile(): void
    {
        $result = $this->service->createSandbox('partner-123', 'nonexistent');

        $this->assertEquals('nonexistent', $result['profile']);
        // Should fall back to basic seed counts
        $this->assertEquals(5, $result['seed_counts']['users']);
        $this->assertEquals(10, $result['seed_counts']['accounts']);
    }

    public function test_get_profiles_returns_three_profiles(): void
    {
        $profiles = $this->service->getProfiles();

        $this->assertCount(3, $profiles);
        $this->assertArrayHasKey('basic', $profiles);
        $this->assertArrayHasKey('full', $profiles);
        $this->assertArrayHasKey('payments', $profiles);
    }

    public function test_basic_profile_has_expected_resource_counts(): void
    {
        $profiles = $this->service->getProfiles();

        $this->assertEquals(5, $profiles['basic']['users']);
        $this->assertEquals(10, $profiles['basic']['accounts']);
        $this->assertEquals(20, $profiles['basic']['transactions']);
    }

    public function test_full_profile_has_expected_resource_counts(): void
    {
        $profiles = $this->service->getProfiles();

        $this->assertEquals(20, $profiles['full']['users']);
        $this->assertEquals(40, $profiles['full']['accounts']);
        $this->assertEquals(200, $profiles['full']['transactions']);
        $this->assertEquals(10, $profiles['full']['loans']);
        $this->assertEquals(5, $profiles['full']['cards']);
        $this->assertEquals(10, $profiles['full']['wallets']);
    }

    public function test_payments_profile_has_expected_resource_counts(): void
    {
        $profiles = $this->service->getProfiles();

        $this->assertEquals(10, $profiles['payments']['users']);
        $this->assertEquals(20, $profiles['payments']['accounts']);
        $this->assertEquals(100, $profiles['payments']['transactions']);
        $this->assertEquals(50, $profiles['payments']['payment_intents']);
    }

    public function test_sandbox_exists_returns_false_by_default(): void
    {
        $exists = $this->service->sandboxExists('any-partner-id');

        $this->assertFalse($exists);
    }
}
