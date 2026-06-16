<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Adapters;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * MAS (Monetary Authority of Singapore) adapter for SG regulatory filings.
 * Handles eServices Gateway: MAS_Returns, STR.
 */
class MASAdapter extends AbstractRegulatoryAdapter
{
    protected function getRegulatorKey(): string
    {
        return 'mas';
    }

    public function getName(): string
    {
        return 'MAS eServices Gateway';
    }

    public function getJurisdiction(): Jurisdiction
    {
        return Jurisdiction::SG;
    }

    /**
     * @return array<string>
     */
    public function getSupportedReportTypes(): array
    {
        return ['MAS_Returns', 'STR'];
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateReport(string $reportType, array $reportData): array
    {
        $errors = $this->validateCommonFields($reportData);

        return match ($reportType) {
            'MAS_Returns' => $this->validateMasReturns($reportData, $errors),
            'STR'         => $this->validateStr($reportData, $errors),
            default       => ['valid' => false, 'errors' => ["Unsupported MAS report type: {$reportType}"]],
        };
    }

    /**
     * Validate MAS Returns (periodic regulatory returns).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateMasReturns(array $data, array $errors): array
    {
        if (empty($data['return_type'])) {
            $errors[] = 'return_type is required for MAS Returns.';
        }

        if (empty($data['reporting_period'])) {
            $errors[] = 'reporting_period is required for MAS Returns.';
        }

        if (empty($data['institution_code'])) {
            $errors[] = 'institution_code is required for MAS Returns.';
        }

        if (empty($data['financial_data'])) {
            $errors[] = 'financial_data is required for MAS Returns.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate Suspicious Transaction Report (threshold: SGD 20,000).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateStr(array $data, array $errors): array
    {
        if (empty($data['suspicious_activity_type'])) {
            $errors[] = 'suspicious_activity_type is required for STR.';
        }

        if (empty($data['narrative'])) {
            $errors[] = 'narrative description is required for STR.';
        }

        if (empty($data['subject_info'])) {
            $errors[] = 'subject_info is required for STR.';
        }

        if (empty($data['transaction_details'])) {
            $errors[] = 'transaction_details is required for STR.';
        }

        if (empty($data['reporting_institution'])) {
            $errors[] = 'reporting_institution is required for STR.';
        }

        if (empty($data['grounds_for_suspicion'])) {
            $errors[] = 'grounds_for_suspicion is required for STR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }
}
