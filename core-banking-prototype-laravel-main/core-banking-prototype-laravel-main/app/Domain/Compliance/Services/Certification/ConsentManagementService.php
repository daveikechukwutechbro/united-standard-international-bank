<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\ConsentRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * Granular consent management with immutable audit trail.
 */
class ConsentManagementService
{
    /**
     * Record a consent decision (always creates a new immutable record).
     *
     * @param  array<string, mixed>  $context
     */
    public function recordConsent(
        string $userUuid,
        string $purpose,
        bool $granted,
        array $context = [],
    ): ConsentRecord {
        return ConsentRecord::create([
            'user_uuid'  => $userUuid,
            'purpose'    => $purpose,
            'version'    => $context['version'] ?? '1.0',
            'granted'    => $granted,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'metadata'   => $context['metadata'] ?? null,
        ]);
    }

    /**
     * Revoke consent for a specific purpose.
     *
     * @param  array<string, mixed>  $context
     */
    public function revokeConsent(string $userUuid, string $purpose, array $context = []): ConsentRecord
    {
        return $this->recordConsent($userUuid, $purpose, false, $context);
    }

    /**
     * Get the current consent status for a user across all purposes.
     *
     * @return array<string, mixed>
     */
    public function getConsentStatus(string $userUuid): array
    {
        $purposes = Config::get('compliance-certification.gdpr.consent_purposes', []);
        $status = [];

        foreach ($purposes as $purposeKey => $purposeConfig) {
            $latestRecord = ConsentRecord::forUser($userUuid)
                ->forPurpose($purposeKey)
                ->orderByDesc('created_at')
                ->first();

            $status[$purposeKey] = [
                'label'       => $purposeConfig['label'],
                'description' => $purposeConfig['description'],
                'required'    => $purposeConfig['required'],
                'granted'     => $latestRecord?->granted ?? false,
                'recorded_at' => $latestRecord?->created_at?->toIso8601String(),
                'version'     => $latestRecord?->version,
            ];
        }

        return $status;
    }

    /**
     * Get the full consent history for a user.
     *
     * @return Collection<int, ConsentRecord>
     */
    public function getConsentHistory(string $userUuid, ?string $purpose = null): Collection
    {
        $query = ConsentRecord::forUser($userUuid);

        if ($purpose) {
            $query->forPurpose($purpose);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Export consent proof for a user (for regulatory audits).
     *
     * @return array<string, mixed>
     */
    public function exportConsentProof(string $userUuid): array
    {
        $history = $this->getConsentHistory($userUuid);

        return [
            'user_uuid'      => $userUuid,
            'exported_at'    => now()->toIso8601String(),
            'current_status' => $this->getConsentStatus($userUuid),
            'total_records'  => $history->count(),
            'history'        => $history->map(fn (ConsentRecord $r) => [
                'purpose'     => $r->purpose,
                'granted'     => $r->granted,
                'version'     => $r->version,
                'ip_address'  => $r->ip_address,
                'recorded_at' => $r->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Get consent coverage statistics.
     *
     * @return array<string, mixed>
     */
    public function getCoverageStats(): array
    {
        $purposes = Config::get('compliance-certification.gdpr.consent_purposes', []);
        $stats = [];

        foreach (array_keys($purposes) as $purpose) {
            $userUuids = ConsentRecord::forPurpose($purpose)
                ->select('user_uuid')
                ->distinct()
                ->pluck('user_uuid');

            $grantedCount = 0;
            foreach ($userUuids as $uuid) {
                $latest = ConsentRecord::forUser($uuid)
                    ->forPurpose($purpose)
                    ->orderByDesc('created_at')
                    ->first();
                if ($latest && $latest->granted) {
                    $grantedCount++;
                }
            }

            $stats[$purpose] = [
                'total_users'  => $userUuids->count(),
                'granted'      => $grantedCount,
                'revoked'      => $userUuids->count() - $grantedCount,
                'consent_rate' => $userUuids->count() > 0
                    ? round(($grantedCount / $userUuids->count()) * 100, 1)
                    : 0,
            ];
        }

        return $stats;
    }

    /**
     * Get demo consent data.
     *
     * @return array<string, mixed>
     */
    public function getDemoStatus(): array
    {
        return [
            'marketing_emails' => [
                'label' => 'Marketing Communications', 'granted' => false, 'required' => false,
            ],
            'analytics' => [
                'label' => 'Analytics & Tracking', 'granted' => true, 'required' => false,
            ],
            'third_party_sharing' => [
                'label' => 'Third-Party Data Sharing', 'granted' => false, 'required' => false,
            ],
            'transaction_processing' => [
                'label' => 'Transaction Processing', 'granted' => true, 'required' => true,
            ],
            'kyc_aml' => [
                'label' => 'KYC/AML Verification', 'granted' => true, 'required' => true,
            ],
        ];
    }
}
