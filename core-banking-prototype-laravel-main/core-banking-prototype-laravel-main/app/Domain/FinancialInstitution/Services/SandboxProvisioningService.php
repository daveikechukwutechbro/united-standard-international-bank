<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SandboxProvisioningService
{
    /** @var array<string, array<string, int>> */
    private const SEED_PROFILES = [
        'basic'    => ['users' => 5, 'accounts' => 10, 'transactions' => 20],
        'full'     => ['users' => 20, 'accounts' => 40, 'transactions' => 200, 'loans' => 10, 'cards' => 5, 'wallets' => 10],
        'payments' => ['users' => 10, 'accounts' => 20, 'transactions' => 100, 'payment_intents' => 50],
    ];

    /**
     * Create a sandbox environment for a partner.
     *
     * @return array{sandbox_id: string, api_key: string, profile: string, seed_counts: array<string, int>}
     */
    public function createSandbox(string $partnerId, string $profile = 'basic'): array
    {
        $seedProfile = self::SEED_PROFILES[$profile] ?? self::SEED_PROFILES['basic'];
        $sandboxId = 'sandbox-' . Str::random(12);
        $apiKey = 'sk_sandbox_' . Str::random(40);

        Log::info('Sandbox created', [
            'partner_id' => $partnerId,
            'sandbox_id' => $sandboxId,
            'profile'    => $profile,
        ]);

        return [
            'sandbox_id'  => $sandboxId,
            'api_key'     => $apiKey,
            'profile'     => $profile,
            'seed_counts' => $seedProfile,
        ];
    }

    /**
     * Get available seed profiles.
     *
     * @return array<string, array<string, int>>
     */
    public function getProfiles(): array
    {
        return self::SEED_PROFILES;
    }

    /**
     * Check if a sandbox exists for a partner.
     */
    public function sandboxExists(string $partnerId): bool
    {
        // In production, this would check the tenant database
        return false;
    }
}
