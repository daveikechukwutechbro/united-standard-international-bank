<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Adapters;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * FinCEN (Financial Crimes Enforcement Network) adapter for US regulatory filings.
 * Handles BSA E-Filing: CTR, SAR, CMIR, FBAR.
 */
class FinCENAdapter extends AbstractRegulatoryAdapter
{
    protected function getRegulatorKey(): string
    {
        return 'fincen';
    }

    public function getName(): string
    {
        return 'FinCEN BSA E-Filing';
    }

    public function getJurisdiction(): Jurisdiction
    {
        return Jurisdiction::US;
    }

    /**
     * @return array<string>
     */
    public function getSupportedReportTypes(): array
    {
        return ['CTR', 'SAR', 'CMIR', 'FBAR'];
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateReport(string $reportType, array $reportData): array
    {
        $errors = $this->validateCommonFields($reportData);

        return match ($reportType) {
            'CTR'   => $this->validateCtr($reportData, $errors),
            'SAR'   => $this->validateSar($reportData, $errors),
            'CMIR'  => $this->validateCmir($reportData, $errors),
            'FBAR'  => $this->validateFbar($reportData, $errors),
            default => ['valid' => false, 'errors' => ["Unsupported FinCEN report type: {$reportType}"]],
        };
    }

    /**
     * Validate Currency Transaction Report (transactions >= $10,000).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateCtr(array $data, array $errors): array
    {
        if (empty($data['transaction_amount'])) {
            $errors[] = 'transaction_amount is required for CTR.';
        } elseif ((float) $data['transaction_amount'] < 10000) {
            $errors[] = 'CTR is required only for transactions of $10,000 or more.';
        }

        if (empty($data['transaction_type'])) {
            $errors[] = 'transaction_type is required for CTR (deposit, withdrawal, exchange, transfer).';
        }

        if (empty($data['financial_institution'])) {
            $errors[] = 'financial_institution name is required for CTR.';
        }

        if (empty($data['conductor_info'])) {
            $errors[] = 'conductor_info (person conducting transaction) is required for CTR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate Suspicious Activity Report.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateSar(array $data, array $errors): array
    {
        if (empty($data['suspicious_activity_type'])) {
            $errors[] = 'suspicious_activity_type is required for SAR.';
        }

        if (empty($data['narrative'])) {
            $errors[] = 'narrative description is required for SAR.';
        }

        if (empty($data['activity_date_range'])) {
            $errors[] = 'activity_date_range is required for SAR.';
        }

        if (empty($data['subject_info'])) {
            $errors[] = 'subject_info is required for SAR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate Report of International Transportation of Currency or Monetary Instruments.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateCmir(array $data, array $errors): array
    {
        if (empty($data['transport_amount'])) {
            $errors[] = 'transport_amount is required for CMIR.';
        } elseif ((float) $data['transport_amount'] < 10000) {
            $errors[] = 'CMIR is required only for amounts of $10,000 or more.';
        }

        if (empty($data['transport_direction'])) {
            $errors[] = 'transport_direction (inbound/outbound) is required for CMIR.';
        }

        if (empty($data['country_of_origin']) && empty($data['country_of_destination'])) {
            $errors[] = 'country_of_origin or country_of_destination is required for CMIR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate Report of Foreign Bank and Financial Accounts.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateFbar(array $data, array $errors): array
    {
        if (empty($data['foreign_accounts'])) {
            $errors[] = 'foreign_accounts list is required for FBAR.';
        }

        if (empty($data['max_account_value'])) {
            $errors[] = 'max_account_value is required for FBAR.';
        } elseif ((float) $data['max_account_value'] < 10000) {
            $errors[] = 'FBAR is required only when aggregate value exceeds $10,000.';
        }

        if (empty($data['tax_year'])) {
            $errors[] = 'tax_year is required for FBAR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }
}
