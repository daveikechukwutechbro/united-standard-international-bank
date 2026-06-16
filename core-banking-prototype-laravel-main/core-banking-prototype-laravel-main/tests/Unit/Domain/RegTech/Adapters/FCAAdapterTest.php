<?php

declare(strict_types=1);

use App\Domain\RegTech\Adapters\FCAAdapter;
use App\Domain\RegTech\Enums\Jurisdiction;

describe('FCAAdapter', function (): void {
    beforeEach(function (): void {
        $this->adapter = new FCAAdapter();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->adapter->getName())->toBe('FCA Gabriel');
        });

        it('handles UK jurisdiction', function (): void {
            expect($this->adapter->getJurisdiction())->toBe(Jurisdiction::UK);
        });

        it('supports MiFID_Transaction, REP-CRIM, SUP16', function (): void {
            expect($this->adapter->getSupportedReportTypes())
                ->toBe(['MiFID_Transaction', 'REP-CRIM', 'SUP16']);
        });
    });

    describe('validateReport', function (): void {
        it('validates UK MiFID Transaction Report with firm_reference', function (): void {
            $result = $this->adapter->validateReport('MiFID_Transaction', [
                'entity_name'           => 'Test UK Firm',
                'reporting_date'        => '2026-01-15',
                'instrument_id'         => 'GB0002634946',
                'transaction_reference' => 'TXN-UK-001',
                'executing_entity_id'   => '213800MBWEIJDM5CU638',
                'quantity'              => 500,
                'price'                 => 12.50,
                'firm_reference'        => '123456',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects UK MiFID without firm_reference', function (): void {
            $result = $this->adapter->validateReport('MiFID_Transaction', [
                'entity_name'           => 'Test UK Firm',
                'reporting_date'        => '2026-01-15',
                'instrument_id'         => 'GB0002634946',
                'transaction_reference' => 'TXN-UK-001',
                'executing_entity_id'   => '213800MBWEIJDM5CU638',
                'quantity'              => 500,
                'price'                 => 12.50,
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('firm_reference (FCA FRN) is required for UK MiFID Transaction Report.');
        });

        it('validates REP-CRIM annual financial crime report', function (): void {
            $result = $this->adapter->validateReport('REP-CRIM', [
                'entity_name'            => 'Test UK Firm',
                'reporting_date'         => '2026-01-15',
                'reporting_period'       => '2025',
                'total_sars_submitted'   => 42,
                'aml_budget'             => 250000,
                'compliance_staff_count' => 15,
                'risk_assessment_date'   => '2025-06-15',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects REP-CRIM without compliance data', function (): void {
            $result = $this->adapter->validateReport('REP-CRIM', [
                'entity_name'    => 'Test UK Firm',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('reporting_period is required for REP-CRIM.')
                ->and($result['errors'])->toContain('total_sars_submitted count is required for REP-CRIM.');
        });

        it('validates SUP16 regulatory transaction reporting', function (): void {
            $result = $this->adapter->validateReport('SUP16', [
                'entity_name'      => 'Test UK Firm',
                'reporting_date'   => '2026-01-15',
                'firm_reference'   => '123456',
                'reporting_period' => 'Q4-2025',
                'transaction_data' => [['id' => 'TX1', 'amount' => 1000]],
                'product_type'     => 'derivatives',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects unsupported report type', function (): void {
            $result = $this->adapter->validateReport('INVALID', []);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('Unsupported FCA report type: INVALID');
        });
    });

    describe('submitReport', function (): void {
        it('submits valid UK MiFID report', function (): void {
            $result = $this->adapter->submitReport('MiFID_Transaction', [
                'entity_name'           => 'Test UK Firm',
                'reporting_date'        => '2026-01-15',
                'instrument_id'         => 'GB0002634946',
                'transaction_reference' => 'TXN-UK-001',
                'executing_entity_id'   => '213800MBWEIJDM5CU638',
                'quantity'              => 500,
                'price'                 => 12.50,
                'firm_reference'        => '123456',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['reference'])->toStartWith('FCA-')
                ->and($result['response']['jurisdiction'])->toBe('UK');
        });
    });
});
