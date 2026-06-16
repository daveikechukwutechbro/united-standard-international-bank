<?php

declare(strict_types=1);

use App\Domain\RegTech\Adapters\ESMAAdapter;
use App\Domain\RegTech\Enums\Jurisdiction;

describe('ESMAAdapter', function (): void {
    beforeEach(function (): void {
        $this->adapter = new ESMAAdapter();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->adapter->getName())->toBe('ESMA FIRDS/TREM');
        });

        it('handles EU jurisdiction', function (): void {
            expect($this->adapter->getJurisdiction())->toBe(Jurisdiction::EU);
        });

        it('supports MiFID_Transaction, EMIR, SFTR', function (): void {
            expect($this->adapter->getSupportedReportTypes())
                ->toBe(['MiFID_Transaction', 'EMIR', 'SFTR']);
        });
    });

    describe('validateReport', function (): void {
        it('validates MiFID Transaction Report', function (): void {
            $result = $this->adapter->validateReport('MiFID_Transaction', [
                'entity_name'           => 'Test Firm',
                'reporting_date'        => '2026-01-15',
                'instrument_id'         => 'US0378331005',
                'transaction_reference' => 'TXN-001',
                'executing_entity_id'   => '529900T8BM49AURSDO55',
                'buyer_id'              => 'CLIENT-001',
                'quantity'              => 100,
                'price'                 => 150.50,
                'venue'                 => 'XNAS',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects MiFID without instrument_id', function (): void {
            $result = $this->adapter->validateReport('MiFID_Transaction', [
                'entity_name'    => 'Test Firm',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('instrument_id (ISIN) is required for MiFID Transaction Report.');
        });

        it('validates EMIR derivative trade report', function (): void {
            $result = $this->adapter->validateReport('EMIR', [
                'entity_name'     => 'Test Firm',
                'reporting_date'  => '2026-01-15',
                'counterparty_id' => '529900T8BM49AURSDO55',
                'trade_id'        => 'UTI-001',
                'derivative_type' => 'interest_rate_swap',
                'notional_amount' => 1000000,
                'maturity_date'   => '2027-01-15',
                'action_type'     => 'new',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects EMIR without counterparty_id', function (): void {
            $result = $this->adapter->validateReport('EMIR', [
                'entity_name'    => 'Test Firm',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('counterparty_id (LEI) is required for EMIR.');
        });

        it('validates SFTR report', function (): void {
            $result = $this->adapter->validateReport('SFTR', [
                'entity_name'      => 'Test Firm',
                'reporting_date'   => '2026-01-15',
                'counterparty_id'  => '529900T8BM49AURSDO55',
                'sft_type'         => 'repo',
                'collateral_info'  => ['type' => 'government_bonds'],
                'principal_amount' => 500000,
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects unsupported report type', function (): void {
            $result = $this->adapter->validateReport('INVALID', []);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('Unsupported ESMA report type: INVALID');
        });
    });

    describe('submitReport', function (): void {
        it('submits valid MiFID report', function (): void {
            $result = $this->adapter->submitReport('MiFID_Transaction', [
                'entity_name'           => 'Test Firm',
                'reporting_date'        => '2026-01-15',
                'instrument_id'         => 'US0378331005',
                'transaction_reference' => 'TXN-001',
                'executing_entity_id'   => '529900T8BM49AURSDO55',
                'buyer_id'              => 'CLIENT-001',
                'quantity'              => 100,
                'price'                 => 150.50,
                'venue'                 => 'XNAS',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['reference'])->toStartWith('ESMA-')
                ->and($result['response']['jurisdiction'])->toBe('EU');
        });
    });
});
