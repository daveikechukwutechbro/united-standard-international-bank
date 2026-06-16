<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Adapters;

use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GoPlus Security API adapter for blockchain address screening.
 *
 * Supports both authenticated (app_key + app_secret) and unauthenticated
 * modes. Authenticated mode provides higher rate limits.
 *
 * Authentication flow:
 * 1. SHA1(app_key + timestamp + app_secret) → sign
 * 2. POST /api/v1/token { app_key, time, sign } → access_token
 * 3. Pass access_token as Authorization header on subsequent requests
 *
 * @see https://docs.gopluslabs.io/reference/api-overview
 */
class GoPlusAdapter implements SanctionsScreeningInterface
{
    private const BASE_URL = 'https://api.gopluslabs.io/api/v1';

    private const TOKEN_CACHE_KEY = 'compliance:goplus_access_token';

    private const TOKEN_CACHE_TTL = 3300; // 55 minutes (tokens typically valid for 1 hour)

    /**
     * GoPlus chain ID mapping.
     *
     * @var array<string, string>
     */
    private const CHAIN_MAP = [
        'ethereum'  => '1',
        'bsc'       => '56',
        'polygon'   => '137',
        'arbitrum'  => '42161',
        'optimism'  => '10',
        'avalanche' => '43114',
        'base'      => '8453',
        'solana'    => 'solana',
    ];

    public function __construct(
        private readonly string $appKey = '',
        private readonly string $appSecret = '',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function screenIndividual(array $searchParams): array
    {
        // GoPlus is address-only; individual screening not supported
        return [
            'matches'       => [],
            'lists_checked' => [],
            'total_matches' => 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function screenAddress(string $address, string $chain = 'ethereum'): array
    {
        $chainId = self::CHAIN_MAP[strtolower($chain)] ?? '1';
        $matches = [];
        $flags = [];

        try {
            $request = Http::timeout(10);

            // Attach authorization if credentials are configured
            $token = $this->getAccessToken();
            if ($token !== null) {
                $request = $request->withHeaders(['Authorization' => $token]);
            }

            $response = $request->get(self::BASE_URL . '/address_security/' . $address, [
                'chain_id' => $chainId,
            ]);

            if (! $response->successful()) {
                Log::warning('GoPlus API returned non-200', [
                    'status'  => $response->status(),
                    'address' => $address,
                    'chain'   => $chain,
                ]);

                return [
                    'matches'       => [],
                    'lists_checked' => ['GoPlus'],
                    'total_matches' => 0,
                ];
            }

            /** @var array<string, mixed> $data */
            $data = $response->json('result') ?? [];

            $flags = $this->analyzeFlags($data, $address);

            if (! empty($flags)) {
                $matches['GoPlus'] = $flags;
            }
        } catch (Throwable $e) {
            Log::error('GoPlus address screening failed', [
                'address' => $address,
                'chain'   => $chain,
                'error'   => $e->getMessage(),
            ]);
        }

        return [
            'matches'       => $matches,
            'lists_checked' => ['GoPlus'],
            'total_matches' => count($flags),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'GoPlus';
    }

    /**
     * Get a cached access token, or request a new one.
     *
     * Returns null if credentials are not configured (unauthenticated mode).
     */
    private function getAccessToken(): ?string
    {
        if ($this->appKey === '' || $this->appSecret === '') {
            return null;
        }

        /** @var string|null $cached */
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $timestamp = (string) time();
            $sign = sha1($this->appKey . $timestamp . $this->appSecret);

            $response = Http::timeout(10)
                ->post(self::BASE_URL . '/token', [
                    'app_key' => $this->appKey,
                    'time'    => $timestamp,
                    'sign'    => $sign,
                ]);

            if (! $response->successful()) {
                Log::warning('GoPlus token request failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $token = $response->json('result.access_token');

            if (! is_string($token) || $token === '') {
                Log::warning('GoPlus token response missing access_token');

                return null;
            }

            Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_CACHE_TTL);

            return $token;
        } catch (Throwable $e) {
            Log::warning('GoPlus token acquisition failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Analyze GoPlus response flags and convert to match records.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{flag: string, description: string, severity: string, address: string}>
     */
    private function analyzeFlags(array $data, string $address): array
    {
        $flags = [];

        // Critical flags — any of these means the address is sanctioned/dangerous
        $criticalChecks = [
            'blacklist_doubt'      => 'Address flagged on blacklists (sanctions/OFAC)',
            'honeypot_related'     => 'Associated with honeypot scams',
            'phishing_activities'  => 'Involved in phishing activities',
            'stealing_attack'      => 'Involved in stealing/exploit attacks',
            'fake_token'           => 'Associated with fake tokens',
            'malicious_mining'     => 'Involved in malicious mining/draining',
            'financial_crime'      => 'Flagged for financial crimes',
            'darkweb_transactions' => 'Associated with darkweb transactions',
            'sanctioned'           => 'On government sanctions lists',
            'mixer'                => 'Identified as a mixer/tumbler',
        ];

        foreach ($criticalChecks as $key => $description) {
            if (isset($data[$key]) && $data[$key] === '1') {
                $flags[] = [
                    'flag'        => $key,
                    'description' => $description,
                    'severity'    => 'critical',
                    'address'     => $address,
                ];
            }
        }

        // Warning flags — suspicious but not necessarily sanctioned
        $warningChecks = [
            'cybercrime'                            => 'Linked to cybercrime activities',
            'money_laundering'                      => 'Associated with money laundering',
            'number_of_malicious_contracts_created' => 'Created malicious contracts',
        ];

        foreach ($warningChecks as $key => $description) {
            $value = $data[$key] ?? '0';

            if ($value === '1' || (is_numeric($value) && (int) $value > 0)) {
                $flags[] = [
                    'flag'        => $key,
                    'description' => $description,
                    'severity'    => 'high',
                    'address'     => $address,
                ];
            }
        }

        return $flags;
    }
}
