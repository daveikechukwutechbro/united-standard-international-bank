<?php

declare(strict_types=1);

use App\Domain\KeyManagement\HSM\AwsKmsHsmProvider;
use App\Domain\KeyManagement\HSM\AzureKeyVaultHsmProvider;
use App\Domain\KeyManagement\HSM\DemoHsmProvider;
use App\Domain\KeyManagement\HSM\HsmProviderFactory;

uses(Tests\TestCase::class);

describe('HsmProviderFactory', function (): void {
    describe('create', function (): void {
        it('creates DemoHsmProvider for demo type', function (): void {
            $factory = new HsmProviderFactory();
            $provider = $factory->create('demo');

            expect($provider)->toBeInstanceOf(DemoHsmProvider::class);
            expect($provider->getProviderName())->toBe('demo');
        });

        it('creates AwsKmsHsmProvider for aws type', function (): void {
            config(['keymanagement.hsm.aws.key_arn' => 'arn:aws:kms:us-east-1:123456789:key/test']);
            config(['keymanagement.hsm.aws.region' => 'us-east-1']);

            $factory = new HsmProviderFactory();
            $provider = $factory->create('aws');

            expect($provider)->toBeInstanceOf(AwsKmsHsmProvider::class);
            expect($provider->getProviderName())->toBe('aws');
        });

        it('creates AzureKeyVaultHsmProvider for azure type', function (): void {
            config(['keymanagement.hsm.azure.vault_url' => 'https://test.vault.azure.net']);
            config(['keymanagement.hsm.azure.key_name' => 'test-key']);
            config(['keymanagement.hsm.azure.tenant_id' => 'tenant-123']);
            config(['keymanagement.hsm.azure.client_id' => 'client-123']);
            config(['keymanagement.hsm.azure.client_secret' => 'secret-123']);

            $factory = new HsmProviderFactory();
            $provider = $factory->create('azure');

            expect($provider)->toBeInstanceOf(AzureKeyVaultHsmProvider::class);
            expect($provider->getProviderName())->toBe('azure');
        });

        it('throws for unknown provider type', function (): void {
            $factory = new HsmProviderFactory();
            $factory->create('unknown');
        })->throws(RuntimeException::class, 'Unknown HSM provider type');

        it('uses config default when no type specified', function (): void {
            config(['keymanagement.hsm.provider' => 'demo']);

            $factory = new HsmProviderFactory();
            $provider = $factory->create();

            expect($provider)->toBeInstanceOf(DemoHsmProvider::class);
        });

        it('throws when AWS key ARN is not configured', function (): void {
            config(['keymanagement.hsm.aws.key_arn' => '']);

            $factory = new HsmProviderFactory();
            $factory->create('aws');
        })->throws(RuntimeException::class, 'AWS KMS key ARN not configured');

        it('throws when Azure vault URL is not configured', function (): void {
            config(['keymanagement.hsm.azure.vault_url' => '']);

            $factory = new HsmProviderFactory();
            $factory->create('azure');
        })->throws(RuntimeException::class, 'Azure Key Vault URL not configured');

        it('throws when Azure AD credentials are missing', function (): void {
            config(['keymanagement.hsm.azure.vault_url' => 'https://test.vault.azure.net']);
            config(['keymanagement.hsm.azure.tenant_id' => '']);
            config(['keymanagement.hsm.azure.client_id' => '']);
            config(['keymanagement.hsm.azure.client_secret' => '']);

            $factory = new HsmProviderFactory();
            $factory->create('azure');
        })->throws(RuntimeException::class, 'Azure AD credentials not configured');
    });
});
