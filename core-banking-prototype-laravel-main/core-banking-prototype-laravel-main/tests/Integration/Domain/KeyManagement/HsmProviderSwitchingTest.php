<?php

declare(strict_types=1);

use App\Domain\KeyManagement\HSM\HsmIntegrationService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Cache::flush();
});

describe('HSM Provider Switching', function (): void {
    it('defaults to demo provider', function (): void {
        config(['keymanagement.hsm.provider' => 'demo']);

        $service = new HsmIntegrationService();
        expect($service->getProviderName())->toBe('demo');
        expect($service->isAvailable())->toBeTrue();
    });

    it('supports injecting a custom provider', function (): void {
        $mockProvider = Mockery::mock(App\Domain\KeyManagement\Contracts\HsmProviderInterface::class);
        $mockProvider->shouldReceive('isAvailable')->andReturn(true);
        $mockProvider->shouldReceive('getProviderName')->andReturn('custom');

        $service = new HsmIntegrationService($mockProvider);
        expect($service->getProviderName())->toBe('custom');
    });

    it('resolves aws provider when configured', function (): void {
        config(['keymanagement.hsm.provider' => 'aws']);
        config(['keymanagement.hsm.aws.key_arn' => 'arn:aws:kms:us-east-1:123456789:key/test']);
        config(['keymanagement.hsm.aws.region' => 'us-east-1']);

        $service = new HsmIntegrationService();
        expect($service->getProviderName())->toBe('aws');
    });

    it('resolves azure provider when configured', function (): void {
        config(['keymanagement.hsm.provider' => 'azure']);
        config(['keymanagement.hsm.azure.vault_url' => 'https://test.vault.azure.net']);
        config(['keymanagement.hsm.azure.key_name' => 'test-key']);
        config(['keymanagement.hsm.azure.tenant_id' => 'tenant-123']);
        config(['keymanagement.hsm.azure.client_id' => 'client-123']);
        config(['keymanagement.hsm.azure.client_secret' => 'secret-123']);

        $service = new HsmIntegrationService();
        expect($service->getProviderName())->toBe('azure');
    });

    it('throws for unknown provider', function (): void {
        config(['keymanagement.hsm.provider' => 'invalid']);
        new HsmIntegrationService();
    })->throws(RuntimeException::class, 'Unknown HSM provider type');

    it('demo provider can encrypt and decrypt', function (): void {
        config(['keymanagement.hsm.provider' => 'demo']);

        $service = new HsmIntegrationService();
        $encrypted = $service->encrypt('test-data');
        $decrypted = $service->decrypt($encrypted);

        expect($decrypted)->toBe('test-data');
    });
});
