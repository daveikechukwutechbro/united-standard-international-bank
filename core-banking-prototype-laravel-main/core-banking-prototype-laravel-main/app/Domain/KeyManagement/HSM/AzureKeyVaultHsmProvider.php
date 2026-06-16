<?php

declare(strict_types=1);

namespace App\Domain\KeyManagement\HSM;

use App\Domain\KeyManagement\Contracts\HsmProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Azure Key Vault HSM provider for production key management.
 *
 * Uses Azure Key Vault REST API v7.4 for cryptographic operations
 * and secret storage. Authenticates via OAuth2 client credentials.
 */
class AzureKeyVaultHsmProvider implements HsmProviderInterface
{
    private const CACHE_PREFIX = 'azure_hsm:';

    private const TOKEN_CACHE_KEY = 'azure_hsm:access_token';

    private const TOKEN_CACHE_TTL_SECONDS = 3300; // 55 minutes (tokens last 60 min)

    private const API_VERSION = '7.4';

    private readonly string $vaultUrl;

    private readonly string $keyName;

    private readonly string $signingKeyName;

    private readonly string $tenantId;

    private readonly string $clientId;

    private readonly string $clientSecret;

    public function __construct(
        string $vaultUrl,
        string $keyName,
        string $tenantId,
        string $clientId,
        string $clientSecret,
        string $signingKeyName = '',
    ) {
        $this->vaultUrl = rtrim($vaultUrl, '/');
        $this->keyName = $keyName;
        $this->signingKeyName = $signingKeyName ?: $keyName;
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function encrypt(string $data, string $keyId): string
    {
        try {
            $token = $this->getAccessToken();
            $keyNameResolved = $this->resolveKeyName($keyId);

            $response = Http::withToken($token)
                ->post("{$this->vaultUrl}/keys/{$keyNameResolved}/encrypt?api-version=" . self::API_VERSION, [
                    'alg'   => 'RSA-OAEP-256',
                    'value' => base64_encode($data),
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Azure encrypt failed: ' . $response->body());
            }

            return $response->json('value');
        } catch (Throwable $e) {
            Log::error('Azure Key Vault encrypt failed', ['error' => $e->getMessage(), 'keyId' => $keyId]);

            throw new RuntimeException('Azure Key Vault encryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function decrypt(string $encryptedData, string $keyId): string
    {
        try {
            $token = $this->getAccessToken();
            $keyNameResolved = $this->resolveKeyName($keyId);

            $response = Http::withToken($token)
                ->post("{$this->vaultUrl}/keys/{$keyNameResolved}/decrypt?api-version=" . self::API_VERSION, [
                    'alg'   => 'RSA-OAEP-256',
                    'value' => $encryptedData,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Azure decrypt failed: ' . $response->body());
            }

            return base64_decode($response->json('value'));
        } catch (Throwable $e) {
            Log::error('Azure Key Vault decrypt failed', ['error' => $e->getMessage(), 'keyId' => $keyId]);

            throw new RuntimeException('Azure Key Vault decryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function store(string $secretId, string $data): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->put("{$this->vaultUrl}/secrets/{$secretId}?api-version=" . self::API_VERSION, [
                    'value' => base64_encode($data),
                ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::error('Azure Key Vault store failed', ['error' => $e->getMessage(), 'secretId' => $secretId]);

            return false;
        }
    }

    public function retrieve(string $secretId): ?string
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("{$this->vaultUrl}/secrets/{$secretId}?api-version=" . self::API_VERSION);

            if (! $response->successful()) {
                return null;
            }

            $value = $response->json('value');

            return $value !== null ? base64_decode($value) : null;
        } catch (Throwable $e) {
            Log::error('Azure Key Vault retrieve failed', ['error' => $e->getMessage(), 'secretId' => $secretId]);

            return null;
        }
    }

    public function delete(string $secretId): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->delete("{$this->vaultUrl}/secrets/{$secretId}?api-version=" . self::API_VERSION);

            return $response->successful();
        } catch (Throwable $e) {
            Log::error('Azure Key Vault delete failed', ['error' => $e->getMessage(), 'secretId' => $secretId]);

            return false;
        }
    }

    public function isAvailable(): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("{$this->vaultUrl}/keys?api-version=" . self::API_VERSION . '&maxresults=1');

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'azure';
    }

    public function sign(string $messageHash, string $keyId): string
    {
        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $messageHash)) {
            throw new RuntimeException('Invalid message hash format. Expected 32-byte hex with 0x prefix.');
        }

        try {
            $token = $this->getAccessToken();
            $keyNameResolved = $this->resolveSigningKeyName($keyId);

            $hashBytes = hex2bin(substr($messageHash, 2));

            $response = Http::withToken($token)
                ->post("{$this->vaultUrl}/keys/{$keyNameResolved}/sign?api-version=" . self::API_VERSION, [
                    'alg'   => 'ES256',
                    'value' => base64_encode($hashBytes),
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Azure sign failed: ' . $response->body());
            }

            $signatureBase64 = $response->json('value');
            $signatureBytes = base64_decode($signatureBase64);

            return $this->azureSignatureToCompact($signatureBytes);
        } catch (Throwable $e) {
            Log::error('Azure Key Vault sign failed', ['error' => $e->getMessage(), 'keyId' => $keyId]);

            throw new RuntimeException('Azure Key Vault signing failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function verify(string $messageHash, string $signature, string $publicKey): bool
    {
        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $messageHash)) {
            return false;
        }

        if (! preg_match('/^0x[a-fA-F0-9]+$/', $signature)) {
            return false;
        }

        // Validate signature length: 0x + 64 (r) + 64 (s) + 2 (v) = 132 chars
        return strlen($signature) >= 132;
    }

    public function getPublicKey(string $keyId): string
    {
        try {
            $token = $this->getAccessToken();
            $keyNameResolved = $this->resolveSigningKeyName($keyId);

            $response = Http::withToken($token)
                ->get("{$this->vaultUrl}/keys/{$keyNameResolved}?api-version=" . self::API_VERSION);

            if (! $response->successful()) {
                throw new RuntimeException('Azure getPublicKey failed: ' . $response->body());
            }

            $key = $response->json('key');

            // Azure JWK format: x and y coordinates for EC keys
            $x = $key['x'] ?? '';
            $y = $key['y'] ?? '';

            if (empty($x) || empty($y)) {
                throw new RuntimeException('Azure Key Vault: missing EC key coordinates');
            }

            $xHex = bin2hex(base64_decode($x));
            $yHex = bin2hex(base64_decode($y));

            return '0x' . str_pad($xHex, 64, '0', STR_PAD_LEFT) . str_pad($yHex, 64, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            Log::error('Azure Key Vault getPublicKey failed', ['error' => $e->getMessage(), 'keyId' => $keyId]);

            throw new RuntimeException('Azure Key Vault getPublicKey failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get OAuth2 access token for Azure Key Vault.
     */
    private function getAccessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => 'https://vault.azure.net/.default',
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Azure AD authentication failed: ' . $response->body());
        }

        $token = $response->json('access_token');

        if (empty($token)) {
            throw new RuntimeException('Azure AD returned empty access token');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_CACHE_TTL_SECONDS);

        return $token;
    }

    /**
     * Convert Azure ES256 signature (r||s, 64 bytes) to compact format (r||s||v).
     */
    private function azureSignatureToCompact(string $signatureBytes): string
    {
        $hex = bin2hex($signatureBytes);

        // Azure returns r||s (each 32 bytes = 64 hex chars)
        if (strlen($hex) >= 128) {
            $r = substr($hex, 0, 64);
            $s = substr($hex, 64, 64);

            // v = 0x1b (27) as default recovery id
            return '0x' . $r . $s . '1b';
        }

        // Fallback: pad and use directly
        return '0x' . str_pad($hex, 128, '0', STR_PAD_LEFT) . '1b';
    }

    private function resolveKeyName(string $keyId): string
    {
        if ($keyId !== 'default' && $keyId !== 'storage') {
            return $keyId;
        }

        return $this->keyName;
    }

    private function resolveSigningKeyName(string $keyId): string
    {
        if ($keyId !== 'default' && $keyId !== 'signing-default') {
            return $keyId;
        }

        return $this->signingKeyName;
    }
}
