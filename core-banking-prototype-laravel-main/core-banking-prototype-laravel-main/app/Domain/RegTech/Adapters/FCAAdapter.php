<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Adapters;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * FCA (Financial Conduct Authority) adapter for UK regulatory filings.
 * Handles Gabriel reporting: MiFID_Transaction, REP-CRIM, SUP16.
 */
class FCAAdapter extends AbstractRegulatoryAdapter
{
    protected function getRegulatorKey(): string
    {
        return 'fca';
    }

    public function getName(): string
    {
        return 'FCA Gabriel';
    }

    public function getJurisdiction(): Jurisdiction
    {
        return Jurisdiction::UK;
    }

    /**
     * @return array<string>
     */
    public function getSupportedReportTypes(): array
    {
        return ['MiFID_Transaction', 'REP-CRIM', 'SUP16'];
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateReport(string $reportType, array $reportData): array
    {
        $errors = $this->validateCommonFields($reportData);

        return match ($reportType) {
            'MiFID_Transaction' => $this->validateMifidTransaction($reportData, $errors),
            'REP-CRIM'          => $this->validateRepCrim($reportData, $errors),
            'SUP16'             => $this->validateSup16($reportData, $errors),
            default             => ['valid' => false, 'errors' => ["Unsupported FCA report type: {$reportType}"]],
        };
    }

    /**
     * Validate MiFID II Transaction Report (UK variant via FCA's Market Data Processor).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateMifidTransaction(array $data, array $errors): array
    {
        if (empty($data['instrument_id'])) {
            $errors[] = 'instrument_id (ISIN) is required for MiFID Transaction Report.';
        }

        if (empty($data['transaction_reference'])) {
            $errors[] = 'transaction_reference is required for MiFID Transaction Report.';
        }

        if (empty($data['executing_entity_id'])) {
            $errors[] = 'executing_entity_id (LEI) is required for MiFID Transaction Report.';
        }

        if (empty($data['quantity'])) {
            $errors[] = 'quantity is required for MiFID Transaction Report.';
        }

        if (empty($data['price'])) {
            $errors[] = 'price is required for MiFID Transaction Report.';
        }

        if (empty($data['firm_reference'])) {
            $errors[] = 'firm_reference (FCA FRN) is required for UK MiFID Transaction Report.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate REP-CRIM (Annual Financial Crime Report).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateRepCrim(array $data, array $errors): array
    {
        if (empty($data['reporting_period'])) {
            $errors[] = 'reporting_period is required for REP-CRIM.';
        }

        if (! isset($data['total_sars_submitted'])) {
            $errors[] = 'total_sars_submitted count is required for REP-CRIM.';
        }

        if (! isset($data['aml_budget'])) {
            $errors[] = 'aml_budget is required for REP-CRIM.';
        }

        if (! isset($data['compliance_staff_count'])) {
            $errors[] = 'compliance_staff_count is required for REP-CRIM.';
        }

        if (empty($data['risk_assessment_date'])) {
            $errors[] = 'risk_assessment_date is required for REP-CRIM.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate SUP16 (Regulatory Transaction Reporting).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateSup16(array $data, array $errors): array
    {
        if (empty($data['firm_reference'])) {
            $errors[] = 'firm_reference (FCA FRN) is required for SUP16.';
        }

        if (empty($data['reporting_period'])) {
            $errors[] = 'reporting_period is required for SUP16.';
        }

        if (empty($data['transaction_data'])) {
            $errors[] = 'transaction_data is required for SUP16.';
        }

        if (empty($data['product_type'])) {
            $errors[] = 'product_type is required for SUP16.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }
}
