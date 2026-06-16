<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\DataClassification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

class DataClassificationService
{
    /**
     * Default classification registry for known sensitive models/fields.
     *
     * @var array<string, array<string, string>>
     */
    private array $defaultRegistry = [
        'App\\Models\\User' => [
            'password'                  => 'restricted',
            'email'                     => 'confidential',
            'remember_token'            => 'restricted',
            'two_factor_secret'         => 'restricted',
            'two_factor_recovery_codes' => 'restricted',
        ],
        'App\\Domain\\Account\\Models\\Account' => [
            'balance'        => 'confidential',
            'account_number' => 'confidential',
        ],
        'App\\Domain\\Wallet\\Models\\Wallet' => [
            'private_key'    => 'restricted',
            'encrypted_seed' => 'restricted',
            'mnemonic'       => 'restricted',
        ],
        'App\\Domain\\Compliance\\Models\\AuditLog' => [
            'old_values' => 'confidential',
            'new_values' => 'confidential',
            'ip_address' => 'internal',
        ],
    ];

    /**
     * Get all classifications, optionally filtered.
     *
     * @return Collection<int, DataClassification>
     */
    public function getClassifications(?string $modelClass = null, ?string $level = null): Collection
    {
        $query = DataClassification::query();

        if ($modelClass) {
            $query->forModel($modelClass);
        }
        if ($level) {
            $query->forLevel($level);
        }

        return $query->get();
    }

    /**
     * Classify a model field.
     */
    public function classifyField(
        string $modelClass,
        string $fieldName,
        string $classificationLevel,
        bool $encryptionRequired = false,
    ): DataClassification {
        $levelConfig = config("compliance-certification.pci_dss.classification_levels.{$classificationLevel}", []);

        return DataClassification::updateOrCreate(
            [
                'model_class' => $modelClass,
                'field_name'  => $fieldName,
            ],
            [
                'classification_level'   => $classificationLevel,
                'encryption_required'    => $encryptionRequired || ($levelConfig['encryption'] ?? false),
                'access_logging_enabled' => $levelConfig['access_logging'] ?? false,
                'retention_days'         => $levelConfig['retention_days'] ?? null,
            ],
        );
    }

    /**
     * Seed default classifications from registry.
     *
     * @return array<string, int>
     */
    public function seedDefaultClassifications(): array
    {
        $created = 0;
        $updated = 0;

        foreach ($this->defaultRegistry as $modelClass => $fields) {
            foreach ($fields as $fieldName => $level) {
                $existing = DataClassification::where('model_class', $modelClass)
                    ->where('field_name', $fieldName)
                    ->first();

                $this->classifyField($modelClass, $fieldName, $level);

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            }
        }

        Log::info('Data classifications seeded', ['created' => $created, 'updated' => $updated]);

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Verify encryption on classified fields using model casts.
     *
     * @return array<string, mixed>
     */
    public function verifyEncryption(): array
    {
        $classifications = DataClassification::requiringEncryption()->get();
        $results = [];

        foreach ($classifications as $classification) {
            $isVerified = $this->checkModelEncryption(
                $classification->model_class,
                $classification->field_name,
            );

            $classification->update(['encryption_verified' => $isVerified]);

            $results[] = [
                'model'               => $classification->model_class,
                'field'               => $classification->field_name,
                'level'               => $classification->classification_level,
                'encryption_verified' => $isVerified,
            ];
        }

        return [
            'total'      => count($results),
            'verified'   => collect($results)->where('encryption_verified', true)->count(),
            'unverified' => collect($results)->where('encryption_verified', false)->count(),
            'details'    => $results,
        ];
    }

    /**
     * Check if a model field uses encryption via casts.
     */
    private function checkModelEncryption(string $modelClass, string $fieldName): bool
    {
        try {
            if (! class_exists($modelClass)) {
                return false;
            }

            $reflection = new ReflectionClass($modelClass);
            $instance = $reflection->newInstanceWithoutConstructor();

            // Check $casts property or getCasts() method
            if (method_exists($instance, 'getCasts')) {
                $casts = $instance->getCasts();
            } else {
                return false;
            }

            if (! isset($casts[$fieldName])) {
                return false;
            }

            $cast = $casts[$fieldName];

            // Check for encrypted casts
            $encryptedCasts = ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:object'];

            return in_array($cast, $encryptedCasts, true)
                || str_starts_with($cast, 'encrypted');
        } catch (Throwable $e) {
            Log::warning("Failed to check encryption for {$modelClass}::{$fieldName}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate a classification completeness report.
     *
     * @return array<string, mixed>
     */
    public function generateComplianceReport(): array
    {
        $classifications = DataClassification::all();
        $byLevel = $classifications->groupBy('classification_level');

        $encryptionRequired = $classifications->where('encryption_required', true);
        $encryptionVerified = $encryptionRequired->where('encryption_verified', true);

        return [
            'total_classifications' => $classifications->count(),
            'by_level'              => $byLevel->map->count()->toArray(),
            'encryption'            => [
                'required'        => $encryptionRequired->count(),
                'verified'        => $encryptionVerified->count(),
                'compliance_rate' => $encryptionRequired->count() > 0
                    ? round(($encryptionVerified->count() / $encryptionRequired->count()) * 100, 2)
                    : 100.0,
            ],
            'unclassified_models' => $this->findUnclassifiedModels(),
            'generated_at'        => now()->toIso8601String(),
        ];
    }

    /**
     * Find models in the default registry that haven't been classified yet.
     *
     * @return array<string>
     */
    private function findUnclassifiedModels(): array
    {
        $unclassified = [];

        foreach ($this->defaultRegistry as $modelClass => $fields) {
            $classifiedCount = DataClassification::where('model_class', $modelClass)->count();
            if ($classifiedCount < count($fields)) {
                $unclassified[] = $modelClass;
            }
        }

        return $unclassified;
    }

    /**
     * Get demo classification data for testing/demo mode.
     *
     * @return array<string, mixed>
     */
    public function getDemoReport(): array
    {
        return [
            'total_classifications' => 47,
            'by_level'              => [
                'public'       => 12,
                'internal'     => 15,
                'confidential' => 13,
                'restricted'   => 7,
            ],
            'encryption' => [
                'required'        => 20,
                'verified'        => 18,
                'compliance_rate' => 90.0,
            ],
            'unclassified_models' => [],
            'generated_at'        => now()->toIso8601String(),
        ];
    }
}
