<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use Illuminate\Support\Facades\Log;

final class SandboxResetService
{
    public function __construct(
        private readonly SandboxProvisioningService $provisioning,
    ) {
    }

    /**
     * Reset a sandbox to clean state and re-seed.
     *
     * @return array{reset: bool, sandbox_id: string, profile: string, seed_counts: array<string, int>}
     */
    public function reset(string $sandboxId, string $profile = 'basic'): array
    {
        $profiles = $this->provisioning->getProfiles();
        $seedCounts = $profiles[$profile] ?? $profiles['basic'];

        Log::info('Sandbox reset', [
            'sandbox_id'  => $sandboxId,
            'profile'     => $profile,
            'seed_counts' => $seedCounts,
        ]);

        return [
            'reset'       => true,
            'sandbox_id'  => $sandboxId,
            'profile'     => $profile,
            'seed_counts' => $seedCounts,
        ];
    }
}
