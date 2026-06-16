<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Enums\ConsentStatus;
use App\Domain\OpenBanking\Models\Consent;
use App\Domain\OpenBanking\Models\ConsentAccessLog;
use Illuminate\Support\Facades\Cache;

final class ConsentEnforcementService
{
    /**
     * Validate that a TPP has valid consent to access the requested resource.
     */
    public function validateAccess(
        string $tppId,
        int $userId,
        string $permission,
        ?string $accountId = null,
    ): bool {
        $consent = Consent::where('tpp_id', $tppId)
            ->where('user_id', $userId)
            ->where('status', ConsentStatus::AUTHORIZED)
            ->first();

        if ($consent === null) {
            return false;
        }

        // Check expiry
        if ($consent->isExpired()) {
            $consent->update(['status' => ConsentStatus::EXPIRED]);

            return false;
        }

        // Check permission
        $permissions = $consent->permissions;
        if (! in_array($permission, $permissions, true)) {
            return false;
        }

        // Check account scope if specified
        if ($accountId !== null && $consent->account_ids !== null) {
            if (! in_array($accountId, $consent->account_ids, true)) {
                return false;
            }
        }

        // Check frequency limit
        if (! $this->checkFrequencyLimit($consent)) {
            return false;
        }

        return true;
    }

    /**
     * Log an access to the consent.
     */
    public function logAccess(
        string $consentId,
        string $tppId,
        string $endpoint,
        ?string $ipAddress = null,
    ): void {
        ConsentAccessLog::create([
            'consent_id' => $consentId,
            'tpp_id'     => $tppId,
            'endpoint'   => $endpoint,
            'ip_address' => $ipAddress,
        ]);

        $key = "ob_consent_freq:{$consentId}:" . now()->format('Y-m-d');
        Cache::add($key, 0, 86400);
        Cache::increment($key);
    }

    /**
     * Check if the consent's daily access frequency limit has been reached.
     */
    public function checkFrequencyLimit(Consent $consent): bool
    {
        $key = "ob_consent_freq:{$consent->id}:" . now()->format('Y-m-d');
        Cache::add($key, 0, 86400);
        $count = (int) Cache::get($key, 0);

        return $count < $consent->frequency_per_day;
    }
}
