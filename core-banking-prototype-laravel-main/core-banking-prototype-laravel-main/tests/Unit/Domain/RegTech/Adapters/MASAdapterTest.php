<?php

declare(strict_types=1);

use App\Domain\RegTech\Adapters\MASAdapter;
use App\Domain\RegTech\Enums\Jurisdiction;

describe('MASAdapter', function (): void {
    beforeEach(function (): void {
        $this->adapter = new MASAdapter();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->adapter->getName())->toBe('MAS eServices Gateway');
        });

        it('handles SG jurisdiction', function (): void {
            expect($this->adapter->getJurisdiction())->toBe(Jurisdiction::SG);
        });

        it('supports MAS_Returns, STR', function (): void {
            expect($this->adapter->getSupportedReportTypes())
                ->toBe(['MAS_Returns', 'STR']);
        });
    });

    describe('validateReport', function (): void {
        it('validates MAS Returns', function (): void {
            $result = $this->adapter->validateReport('MAS_Returns', [
                'entity_name'      => 'Test SG Firm',
                'reporting_date'   => '2026-01-15',
                'return_type'      => 'capital_adequacy',
                'reporting_period' => 'Q4-2025',
                'institution_code' => 'MAS-001',
                'financial_data'   => ['total_assets' => 1000000],
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects MAS Returns without institution code', function (): void {
            $result = $this->adapter->validateReport('MAS_Returns', [
                'entity_name'    => 'Test SG Firm',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('institution_code is required for MAS Returns.');
        });

        it('validates STR (Suspicious Transaction Report)', function (): void {
            $result = $this->adapter->validateReport('STR', [
                'entity_name'              => 'Test SG Firm',
                'reporting_date'           => '2026-01-15',
                'suspicious_activity_type' => 'money_laundering',
                'narrative'                => 'Multiple large cash deposits.',
                'subject_info'             => ['name' => 'Suspect Doe'],
                'transaction_details'      => [['amount' => 25000, 'date' => '2026-01-10']],
                'reporting_institution'    => 'Test SG Bank',
                'grounds_for_suspicion'    => 'Unusual cash deposit pattern.',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects STR without grounds for suspicion', function (): void {
            $result = $this->adapter->validateReport('STR', [
                'entity_name'    => 'Test SG Firm',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('grounds_for_suspicion is required for STR.');
        });

        it('rejects unsupported report type', function (): void {
            $result = $this->adapter->validateReport('INVALID', []);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('Unsupported MAS report type: INVALID');
        });
    });

    describe('submitReport', function (): void {
        it('submits valid MAS Returns', function (): void {
            $result = $this->adapter->submitReport('MAS_Returns', [
                'entity_name'      => 'Test SG Firm',
                'reporting_date'   => '2026-01-15',
                'return_type'      => 'capital_adequacy',
                'reporting_period' => 'Q4-2025',
                'institution_code' => 'MAS-001',
                'financial_data'   => ['total_assets' => 1000000],
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['reference'])->toStartWith('MAS-')
                ->and($result['response']['jurisdiction'])->toBe('SG');
        });
    });

    describe('checkStatus', function (): void {
        it('returns accepted status', function (): void {
            $result = $this->adapter->checkStatus('MAS-20260115-ABCD1234');

            expect($result['status'])->toBe('accepted')
                ->and($result['details']['adapter'])->toBe('MAS eServices Gateway')
                ->and($result['details']['jurisdiction'])->toBe('SG');
        });
    });
});
