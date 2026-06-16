<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\KeyRotationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class KeyRotationService
{
    /**
     * Get key inventory.
     *
     * @return Collection<int, KeyRotationSchedule>
     */
    public function getKeyInventory(?string $status = null): Collection
    {
        $query = KeyRotationSchedule::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('next_rotation_at')->get();
    }

    /**
     * Register a key for rotation tracking.
     */
    public function registerKey(
        string $keyType,
        string $keyIdentifier,
        ?int $rotationIntervalDays = null,
        ?string $algorithm = null,
    ): KeyRotationSchedule {
        $intervalDays = $rotationIntervalDays
            ?? (int) Config::get('compliance-certification.pci_dss.key_rotation.interval_days', 90);
        $algo = $algorithm
            ?? Config::get('compliance-certification.pci_dss.key_rotation.algorithm', 'AES-256-GCM');

        return KeyRotationSchedule::updateOrCreate(
            ['key_identifier' => $keyIdentifier],
            [
                'key_type'               => $keyType,
                'rotation_interval_days' => $intervalDays,
                'algorithm'              => $algo,
                'last_rotated_at'        => Carbon::now(),
                'next_rotation_at'       => Carbon::now()->addDays($intervalDays),
                'status'                 => 'active',
            ],
        );
    }

    /**
     * Initialize default key inventory.
     *
     * @return array<string, int>
     */
    public function initializeDefaultKeys(): array
    {
        $defaultKeys = [
            ['type' => 'app_key', 'identifier' => 'laravel_app_key', 'interval' => 90],
            ['type' => 'encryption_key', 'identifier' => 'aes_256_gcm_primary', 'interval' => 90],
            ['type' => 'jwt_secret', 'identifier' => 'sanctum_jwt_secret', 'interval' => 60],
            ['type' => 'api_token', 'identifier' => 'external_api_keys', 'interval' => 30],
            ['type' => 'session_key', 'identifier' => 'session_encryption_key', 'interval' => 30],
            ['type' => 'webhook_secret', 'identifier' => 'webhook_signing_keys', 'interval' => 90],
        ];

        $registered = 0;

        foreach ($defaultKeys as $key) {
            $existing = KeyRotationSchedule::where('key_identifier', $key['identifier'])->first();
            if (! $existing) {
                $this->registerKey($key['type'], $key['identifier'], $key['interval']);
                $registered++;
            }
        }

        Log::info('Default key inventory initialized', ['registered' => $registered]);

        return ['registered' => $registered, 'total' => count($defaultKeys)];
    }

    /**
     * Get keys that are overdue for rotation.
     *
     * @return Collection<int, KeyRotationSchedule>
     */
    public function getOverdueKeys(): Collection
    {
        return KeyRotationSchedule::overdue()->get();
    }

    /**
     * Get keys due for rotation soon.
     *
     * @return Collection<int, KeyRotationSchedule>
     */
    public function getKeysDueSoon(?int $days = null): Collection
    {
        $notifyDays = $days
            ?? (int) Config::get('compliance-certification.pci_dss.key_rotation.notify_before_days', 14);

        return KeyRotationSchedule::dueSoon($notifyDays)->get();
    }

    /**
     * Rotate a key (demo-safe: logs action, updates timestamps, doesn't change actual keys).
     *
     * @return array<string, mixed>
     */
    public function rotateKey(string $keyIdentifier, bool $dryRun = false): array
    {
        $schedule = KeyRotationSchedule::where('key_identifier', $keyIdentifier)->first();

        if (! $schedule) {
            return [
                'success' => false,
                'message' => "Key '{$keyIdentifier}' not found in rotation schedule",
            ];
        }

        if ($dryRun) {
            return [
                'success'                => true,
                'dry_run'                => true,
                'key_identifier'         => $keyIdentifier,
                'key_type'               => $schedule->key_type,
                'would_rotate_at'        => Carbon::now()->toIso8601String(),
                'next_rotation_would_be' => Carbon::now()->addDays($schedule->rotation_interval_days)->toIso8601String(),
            ];
        }

        // In demo mode, just update tracking — don't actually rotate keys
        $schedule->recordRotation('system');

        Log::info('Key rotated', [
            'key_identifier'   => $keyIdentifier,
            'key_type'         => $schedule->key_type,
            'next_rotation_at' => $schedule->next_rotation_at->toIso8601String(),
        ]);

        return [
            'success'          => true,
            'key_identifier'   => $keyIdentifier,
            'key_type'         => $schedule->key_type,
            'rotated_at'       => Carbon::now()->toIso8601String(),
            'next_rotation_at' => $schedule->next_rotation_at->toIso8601String(),
        ];
    }

    /**
     * Generate key rotation status report.
     *
     * @return array<string, mixed>
     */
    public function generateRotationReport(): array
    {
        $allKeys = KeyRotationSchedule::all();
        $overdue = KeyRotationSchedule::overdue()->count();
        $dueSoon = $this->getKeysDueSoon()->count();

        $byType = $allKeys->groupBy('key_type')->map(function ($keys) {
            return [
                'count'   => $keys->count(),
                'overdue' => $keys->filter->isOverdue()->count(),
            ];
        })->toArray();

        return [
            'total_keys'      => $allKeys->count(),
            'active'          => $allKeys->where('status', 'active')->count(),
            'overdue'         => $overdue,
            'due_soon'        => $dueSoon,
            'by_type'         => $byType,
            'compliance_rate' => $allKeys->count() > 0
                ? round((($allKeys->count() - $overdue) / $allKeys->count()) * 100, 2)
                : 100.0,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get demo rotation report.
     *
     * @return array<string, mixed>
     */
    public function getDemoReport(): array
    {
        return [
            'total_keys' => 6,
            'active'     => 6,
            'overdue'    => 0,
            'due_soon'   => 1,
            'by_type'    => [
                'app_key'        => ['count' => 1, 'overdue' => 0],
                'encryption_key' => ['count' => 1, 'overdue' => 0],
                'jwt_secret'     => ['count' => 1, 'overdue' => 0],
                'api_token'      => ['count' => 1, 'overdue' => 0],
                'session_key'    => ['count' => 1, 'overdue' => 0],
                'webhook_secret' => ['count' => 1, 'overdue' => 0],
            ],
            'compliance_rate' => 100.0,
            'generated_at'    => now()->toIso8601String(),
        ];
    }
}
