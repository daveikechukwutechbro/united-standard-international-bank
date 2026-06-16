<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Services;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * Travel Rule compliance service for cross-border crypto transfers.
 *
 * Implements FATF Recommendation 16 / MiCA / FinCEN travel rule
 * requirements for Virtual Asset Service Providers (VASPs).
 */
class TravelRuleService
{
    public function __construct(
        private readonly JurisdictionConfigurationService $jurisdictionService,
        private readonly MicaComplianceService $micaService
    ) {
    }

    /**
     * Evaluate travel rule compliance for a transfer.
     *
     * @param  array<string, mixed>  $transfer
     * @return array{compliant: bool, jurisdiction: string, threshold: float, amount: float, errors: array<string>, required_data: array<string, mixed>}
     */
    public function evaluate(array $transfer): array
    {
        $amount = (float) ($transfer['amount'] ?? 0);
        $currency = $transfer['currency'] ?? 'EUR';
        $jurisdiction = $this->jurisdictionService->getJurisdictionByCurrency($currency);

        if (! $jurisdiction) {
            return [
                'compliant'     => false,
                'jurisdiction'  => 'unknown',
                'threshold'     => 0,
                'amount'        => $amount,
                'errors'        => ['Unable to determine jurisdiction for currency: ' . $currency],
                'required_data' => [],
            ];
        }

        $threshold = $this->getThreshold($jurisdiction);
        $requiresCompliance = $amount >= $threshold;

        if (! $requiresCompliance) {
            return [
                'compliant'     => true,
                'jurisdiction'  => $jurisdiction->value,
                'threshold'     => $threshold,
                'amount'        => $amount,
                'errors'        => [],
                'required_data' => [],
            ];
        }

        $errors = [];
        $requiredData = $this->getRequiredData($jurisdiction);
        $originator = $transfer['originator'] ?? [];
        $beneficiary = $transfer['beneficiary'] ?? [];

        // Validate originator data
        foreach ($requiredData['originator'] as $field) {
            if (empty($originator[$field])) {
                $errors[] = "Missing originator field: {$field}";
            }
        }

        // Validate beneficiary data
        foreach ($requiredData['beneficiary'] as $field) {
            if (empty($beneficiary[$field])) {
                $errors[] = "Missing beneficiary field: {$field}";
            }
        }

        return [
            'compliant'     => $errors === [],
            'jurisdiction'  => $jurisdiction->value,
            'threshold'     => $threshold,
            'amount'        => $amount,
            'errors'        => $errors,
            'required_data' => $requiredData,
        ];
    }

    /**
     * Get travel rule thresholds for all jurisdictions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getThresholds(): array
    {
        return [
            'US' => [
                'threshold'  => 3000.0,
                'currency'   => 'USD',
                'regulation' => 'FinCEN Travel Rule (31 CFR 1010.410)',
                'applies_to' => 'All financial institutions and MSBs',
            ],
            'EU' => [
                'threshold'  => 1000.0,
                'currency'   => 'EUR',
                'regulation' => 'MiCA Travel Rule (Regulation 2023/1114)',
                'applies_to' => 'CASPs operating in the EU',
            ],
            'UK' => [
                'threshold'  => 1000.0,
                'currency'   => 'GBP',
                'regulation' => 'FCA Travel Rule (MLR 2017 as amended)',
                'applies_to' => 'Cryptoasset businesses registered with FCA',
            ],
            'SG' => [
                'threshold'  => 1500.0,
                'currency'   => 'SGD',
                'regulation' => 'MAS Notice PSN02',
                'applies_to' => 'Digital payment token service providers',
            ],
        ];
    }

    /**
     * Get the travel rule threshold for a jurisdiction.
     */
    public function getThreshold(Jurisdiction $jurisdiction): float
    {
        if ($jurisdiction === Jurisdiction::EU) {
            $micaConfig = $this->jurisdictionService->getMicaConfig();

            return (float) ($micaConfig['travel_rule']['threshold_eur'] ?? 1000);
        }

        return match ($jurisdiction) {
            Jurisdiction::US => 3000.0,
            Jurisdiction::UK => 1000.0,
            Jurisdiction::SG => 1500.0,
        };
    }

    /**
     * Get required originator/beneficiary data by jurisdiction.
     *
     * @return array{originator: array<string>, beneficiary: array<string>}
     */
    public function getRequiredData(Jurisdiction $jurisdiction): array
    {
        return match ($jurisdiction) {
            Jurisdiction::US => [
                'originator'  => ['name', 'account_number', 'address'],
                'beneficiary' => ['name', 'account_number'],
            ],
            Jurisdiction::EU => $this->getEuRequiredData(),
            Jurisdiction::UK => [
                'originator'  => ['name', 'account_number', 'address'],
                'beneficiary' => ['name', 'account_number'],
            ],
            Jurisdiction::SG => [
                'originator'  => ['name', 'account_number', 'address', 'doc_id'],
                'beneficiary' => ['name', 'account_number'],
            ],
        };
    }

    /**
     * Get EU-specific required data from MiCA config.
     *
     * @return array{originator: array<string>, beneficiary: array<string>}
     */
    private function getEuRequiredData(): array
    {
        $micaConfig = $this->jurisdictionService->getMicaConfig();

        return [
            'originator'  => $micaConfig['travel_rule']['required_originator_info'] ?? ['name', 'address', 'account_number', 'doc_id'],
            'beneficiary' => $micaConfig['travel_rule']['required_beneficiary_info'] ?? ['name', 'account_number'],
        ];
    }

    /**
     * Get compliance summary across all jurisdictions for a VASP.
     *
     * @return array<string, mixed>
     */
    public function getComplianceSummary(): array
    {
        return [
            'thresholds'              => $this->getThresholds(),
            'mica_status'             => $this->micaService->getComplianceStatus(),
            'supported_jurisdictions' => Jurisdiction::values(),
            'generated_at'            => now()->toIso8601String(),
        ];
    }
}
