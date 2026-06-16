<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Services;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * MiCA (Markets in Crypto-Assets Regulation) compliance service.
 *
 * Manages CASP authorization, whitepaper validation,
 * reserve management, and travel rule compliance for crypto assets.
 */
class MicaComplianceService
{
    public function __construct(
        private readonly JurisdictionConfigurationService $jurisdictionService
    ) {
    }

    /**
     * Check if MiCA is enabled.
     */
    public function isEnabled(): bool
    {
        $config = $this->jurisdictionService->getMicaConfig();

        return (bool) ($config['enabled'] ?? true);
    }

    /**
     * Check if CASP (Crypto-Asset Service Provider) is authorized.
     */
    public function isCaspAuthorized(): bool
    {
        $config = $this->jurisdictionService->getMicaConfig();

        return (bool) ($config['casp_authorization'] ?? false);
    }

    /**
     * Check if demo mode is active.
     */
    private function isDemoMode(): bool
    {
        $micaConfig = $this->jurisdictionService->getMicaConfig();

        return (bool) ($micaConfig['demo_mode'] ?? true);
    }

    /**
     * Get MiCA compliance status overview.
     *
     * @return array<string, mixed>
     */
    public function getComplianceStatus(): array
    {
        $config = $this->jurisdictionService->getMicaConfig();

        return [
            'enabled'         => $this->isEnabled(),
            'casp_authorized' => $this->isCaspAuthorized(),
            'applicable'      => $this->jurisdictionService->isMicaApplicable(Jurisdiction::EU),
            'travel_rule'     => [
                'enabled'       => $config['travel_rule']['enabled'] ?? true,
                'threshold_eur' => $config['travel_rule']['threshold_eur'] ?? 1000,
            ],
            'reserve_management' => [
                'art_reserve_ratio'  => $config['reserve_management']['art_reserve_ratio'] ?? 1.0,
                'emt_reserve_ratio'  => $config['reserve_management']['emt_reserve_ratio'] ?? 1.0,
                'audit_frequency'    => $config['reserve_management']['audit_frequency'] ?? 'monthly',
                'custodian_required' => $config['reserve_management']['custodian_required'] ?? true,
            ],
            'whitepaper' => [
                'max_pages'         => $config['whitepaper_requirements']['max_pages'] ?? 40,
                'required_sections' => $config['whitepaper_requirements']['required_sections'] ?? [],
            ],
            'last_audit'     => now()->subDays(15)->toIso8601String(),
            'next_audit_due' => now()->addDays(15)->toIso8601String(),
            'demo_mode'      => $this->isDemoMode(),
        ];
    }

    /**
     * Validate a crypto-asset whitepaper against MiCA requirements.
     *
     * @param  array<string, mixed>  $whitepaper
     * @return array{valid: bool, errors: array<string>, warnings: array<string>}
     */
    public function validateWhitepaper(array $whitepaper): array
    {
        $config = $this->jurisdictionService->getMicaConfig();
        $errors = [];
        $warnings = [];

        $requiredSections = $config['whitepaper_requirements']['required_sections'] ?? [];

        foreach ($requiredSections as $section) {
            if (empty($whitepaper['sections'][$section])) {
                $errors[] = "Required section missing: {$section}";
            }
        }

        $maxPages = $config['whitepaper_requirements']['max_pages'] ?? 40;
        $pageCount = $whitepaper['page_count'] ?? 0;

        if ($pageCount > $maxPages) {
            $errors[] = "Whitepaper exceeds maximum page count ({$pageCount}/{$maxPages}).";
        }

        if ($pageCount > 0 && $pageCount < 5) {
            $warnings[] = 'Whitepaper appears unusually short for regulatory compliance.';
        }

        if (empty($whitepaper['issuer_legal_name'])) {
            $errors[] = 'issuer_legal_name is required.';
        }

        if (empty($whitepaper['publication_date'])) {
            $warnings[] = 'publication_date is recommended.';
        }

        return [
            'valid'    => $errors === [],
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get reserve management status for asset-referenced tokens.
     *
     * @return array<string, mixed>
     */
    public function getReserveStatus(): array
    {
        $config = $this->jurisdictionService->getMicaConfig();

        return [
            'art_reserve_ratio'  => $config['reserve_management']['art_reserve_ratio'] ?? 1.0,
            'emt_reserve_ratio'  => $config['reserve_management']['emt_reserve_ratio'] ?? 1.0,
            'custodian_required' => $config['reserve_management']['custodian_required'] ?? true,
            'audit_frequency'    => $config['reserve_management']['audit_frequency'] ?? 'monthly',
            'current_reserves'   => [
                'total_liabilities' => 10000000.00,
                'total_reserves'    => 10250000.00,
                'reserve_ratio'     => 1.025,
                'compliant'         => true,
            ],
            'last_audit'     => now()->subDays(15)->toIso8601String(),
            'next_audit_due' => now()->addDays(15)->toIso8601String(),
            'demo_mode'      => $this->isDemoMode(),
        ];
    }

    /**
     * Check if a crypto transaction requires MiCA travel rule compliance.
     *
     * @param  array<string, mixed>  $transaction
     * @return array{required: bool, threshold: float, amount: float, missing_fields: array<string>}
     */
    public function checkTravelRuleRequirement(array $transaction): array
    {
        $config = $this->jurisdictionService->getMicaConfig();
        $travelRule = $config['travel_rule'] ?? [];

        $amount = (float) ($transaction['amount'] ?? 0);
        $threshold = (float) ($travelRule['threshold_eur'] ?? 1000);

        $required = ($travelRule['enabled'] ?? true) && $amount >= $threshold;

        $missingFields = [];

        if ($required) {
            $requiredOriginator = $travelRule['required_originator_info'] ?? [];
            $requiredBeneficiary = $travelRule['required_beneficiary_info'] ?? [];
            $originator = $transaction['originator'] ?? [];
            $beneficiary = $transaction['beneficiary'] ?? [];

            foreach ($requiredOriginator as $field) {
                if (empty($originator[$field])) {
                    $missingFields[] = "originator.{$field}";
                }
            }

            foreach ($requiredBeneficiary as $field) {
                if (empty($beneficiary[$field])) {
                    $missingFields[] = "beneficiary.{$field}";
                }
            }
        }

        return [
            'required'       => $required,
            'threshold'      => $threshold,
            'amount'         => $amount,
            'missing_fields' => $missingFields,
        ];
    }
}
