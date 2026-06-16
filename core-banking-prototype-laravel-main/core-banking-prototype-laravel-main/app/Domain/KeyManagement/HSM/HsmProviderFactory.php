<?php

declare(strict_types=1);

namespace App\Domain\KeyManagement\HSM;

use App\Domain\KeyManagement\Contracts\HsmProviderInterface;
use Aws\Kms\KmsClient;
use RuntimeException;

/**
 * Factory for creating HSM provider instances based on configuration.
 */
class HsmProviderFactory
{
    /**
     * Create an HSM provider instance.
     *
     * @param  string|null  $type  Provider type ('demo', 'aws', 'azure'). Defaults to config value.
     */
    public function create(?string $type = null): HsmProviderInterface
    {
        $type = $type ?? (string) config('keymanagement.hsm.provider', 'demo');

        return match ($type) {
            'demo'  => new DemoHsmProvider(),
            'aws'   => $this->createAwsProvider(),
            'azure' => $this->createAzureProvider(),
            default => throw new RuntimeException("Unknown HSM provider type: {$type}"),
        };
    }

    private function createAwsProvider(): AwsKmsHsmProvider
    {
        $region = (string) config('keymanagement.hsm.aws.region', 'us-east-1');
        $keyArn = (string) config('keymanagement.hsm.aws.key_arn', '');
        $signingKeyArn = (string) config('keymanagement.hsm.aws.signing_key_arn', '');

        if (empty($keyArn)) {
            throw new RuntimeException('AWS KMS key ARN not configured. Set AWS_KMS_KEY_ARN in environment.');
        }

        $clientConfig = [
            'version' => 'latest',
            'region'  => $region,
        ];

        $accessKey = (string) config('keymanagement.hsm.aws.access_key', '');
        $secretKey = (string) config('keymanagement.hsm.aws.secret_key', '');

        if (! empty($accessKey) && ! empty($secretKey)) {
            $clientConfig['credentials'] = [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ];
        }

        $endpoint = (string) config('keymanagement.hsm.aws.endpoint', '');
        if (! empty($endpoint)) {
            $clientConfig['endpoint'] = $endpoint;
        }

        $kmsClient = new KmsClient($clientConfig);

        return new AwsKmsHsmProvider($kmsClient, $keyArn, $signingKeyArn);
    }

    private function createAzureProvider(): AzureKeyVaultHsmProvider
    {
        $vaultUrl = (string) config('keymanagement.hsm.azure.vault_url', '');
        $keyName = (string) config('keymanagement.hsm.azure.key_name', '');
        $signingKeyName = (string) config('keymanagement.hsm.azure.signing_key_name', '');
        $tenantId = (string) config('keymanagement.hsm.azure.tenant_id', '');
        $clientId = (string) config('keymanagement.hsm.azure.client_id', '');
        $clientSecret = (string) config('keymanagement.hsm.azure.client_secret', '');

        if (empty($vaultUrl)) {
            throw new RuntimeException('Azure Key Vault URL not configured. Set AZURE_KEY_VAULT_URL in environment.');
        }

        if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
            throw new RuntimeException('Azure AD credentials not configured. Set AZURE_TENANT_ID, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET.');
        }

        return new AzureKeyVaultHsmProvider(
            vaultUrl: $vaultUrl,
            keyName: $keyName,
            tenantId: $tenantId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            signingKeyName: $signingKeyName,
        );
    }
}
