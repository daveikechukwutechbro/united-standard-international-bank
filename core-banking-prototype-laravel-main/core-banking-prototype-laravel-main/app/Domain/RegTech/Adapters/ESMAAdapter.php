<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Adapters;

use App\Domain\RegTech\Enums\Jurisdiction;

/**
 * ESMA (European Securities and Markets Authority) adapter for EU regulatory filings.
 * Handles FIRDS/TREM: MiFID_Transaction, EMIR, SFTR.
 */
class ESMAAdapter extends AbstractRegulatoryAdapter
{
    protected function getRegulatorKey(): string
    {
        return 'esma';
    }

    public function getName(): string
    {
        return 'ESMA FIRDS/TREM';
    }

    public function getJurisdiction(): Jurisdiction
    {
        return Jurisdiction::EU;
    }

    /**
     * @return array<string>
     */
    public function getSupportedReportTypes(): array
    {
        return ['MiFID_Transaction', 'EMIR', 'SFTR'];
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
            'EMIR'              => $this->validateEmir($reportData, $errors),
            'SFTR'              => $this->validateSftr($reportData, $errors),
            default             => ['valid' => false, 'errors' => ["Unsupported ESMA report type: {$reportType}"]],
        };
    }

    /**
     * Validate MiFID II Transaction Report (RTS 25 format, T+1 deadline).
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

        if (empty($data['buyer_id']) && empty($data['seller_id'])) {
            $errors[] = 'buyer_id or seller_id is required for MiFID Transaction Report.';
        }

        if (empty($data['quantity'])) {
            $errors[] = 'quantity is required for MiFID Transaction Report.';
        }

        if (empty($data['price'])) {
            $errors[] = 'price is required for MiFID Transaction Report.';
        }

        if (empty($data['venue'])) {
            $errors[] = 'venue (MIC code) is required for MiFID Transaction Report.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate EMIR (European Market Infrastructure Regulation) derivative trade report.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateEmir(array $data, array $errors): array
    {
        if (empty($data['counterparty_id'])) {
            $errors[] = 'counterparty_id (LEI) is required for EMIR.';
        }

        if (empty($data['trade_id'])) {
            $errors[] = 'trade_id (UTI) is required for EMIR.';
        }

        if (empty($data['derivative_type'])) {
            $errors[] = 'derivative_type is required for EMIR.';
        }

        if (empty($data['notional_amount'])) {
            $errors[] = 'notional_amount is required for EMIR.';
        }

        if (empty($data['maturity_date'])) {
            $errors[] = 'maturity_date is required for EMIR.';
        }

        if (empty($data['action_type'])) {
            $errors[] = 'action_type (new, modify, cancel) is required for EMIR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * Validate SFTR (Securities Financing Transactions Regulation) report.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @return array{valid: bool, errors: array<string>}
     */
    private function validateSftr(array $data, array $errors): array
    {
        if (empty($data['counterparty_id'])) {
            $errors[] = 'counterparty_id (LEI) is required for SFTR.';
        }

        if (empty($data['sft_type'])) {
            $errors[] = 'sft_type (repo, securities_lending, margin_lending, buy_sell_back) is required for SFTR.';
        }

        if (empty($data['collateral_info'])) {
            $errors[] = 'collateral_info is required for SFTR.';
        }

        if (empty($data['principal_amount'])) {
            $errors[] = 'principal_amount is required for SFTR.';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }
}
