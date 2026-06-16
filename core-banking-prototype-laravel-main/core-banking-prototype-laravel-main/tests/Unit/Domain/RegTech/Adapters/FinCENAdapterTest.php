<?php

declare(strict_types=1);

use App\Domain\RegTech\Adapters\FinCENAdapter;
use App\Domain\RegTech\Enums\Jurisdiction;

describe('FinCENAdapter', function (): void {
    beforeEach(function (): void {
        $this->adapter = new FinCENAdapter();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->adapter->getName())->toBe('FinCEN BSA E-Filing');
        });

        it('handles US jurisdiction', function (): void {
            expect($this->adapter->getJurisdiction())->toBe(Jurisdiction::US);
        });

        it('supports CTR, SAR, CMIR, FBAR', function (): void {
            expect($this->adapter->getSupportedReportTypes())
                ->toBe(['CTR', 'SAR', 'CMIR', 'FBAR']);
        });

        it('is available', function (): void {
            expect($this->adapter->isAvailable())->toBeTrue();
        });

        it('runs in sandbox mode', function (): void {
            expect($this->adapter->isSandboxMode())->toBeTrue();
        });
    });

    describe('validateReport', function (): void {
        it('validates CTR requires transaction amount >= 10000', function (): void {
            $result = $this->adapter->validateReport('CTR', [
                'entity_name'           => 'Test Corp',
                'reporting_date'        => '2026-01-15',
                'transaction_amount'    => 15000,
                'transaction_type'      => 'deposit',
                'financial_institution' => 'Test Bank',
                'conductor_info'        => ['name' => 'John Doe'],
            ]);

            expect($result['valid'])->toBeTrue()
                ->and($result['errors'])->toBeEmpty();
        });

        it('rejects CTR below threshold', function (): void {
            $result = $this->adapter->validateReport('CTR', [
                'entity_name'           => 'Test Corp',
                'reporting_date'        => '2026-01-15',
                'transaction_amount'    => 5000,
                'transaction_type'      => 'deposit',
                'financial_institution' => 'Test Bank',
                'conductor_info'        => ['name' => 'John Doe'],
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('CTR is required only for transactions of $10,000 or more.');
        });

        it('validates SAR requires narrative and subject info', function (): void {
            $result = $this->adapter->validateReport('SAR', [
                'entity_name'              => 'Test Corp',
                'reporting_date'           => '2026-01-15',
                'suspicious_activity_type' => 'structuring',
                'narrative'                => 'Suspected structuring activity.',
                'activity_date_range'      => ['start' => '2026-01-01', 'end' => '2026-01-15'],
                'subject_info'             => ['name' => 'Jane Doe'],
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects SAR without narrative', function (): void {
            $result = $this->adapter->validateReport('SAR', [
                'entity_name'    => 'Test Corp',
                'reporting_date' => '2026-01-15',
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('narrative description is required for SAR.');
        });

        it('validates CMIR requires transport details', function (): void {
            $result = $this->adapter->validateReport('CMIR', [
                'entity_name'            => 'Test Corp',
                'reporting_date'         => '2026-01-15',
                'transport_amount'       => 50000,
                'transport_direction'    => 'outbound',
                'country_of_destination' => 'CH',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('validates FBAR requires foreign accounts', function (): void {
            $result = $this->adapter->validateReport('FBAR', [
                'entity_name'       => 'Test Corp',
                'reporting_date'    => '2026-01-15',
                'foreign_accounts'  => [['bank' => 'Swiss Bank', 'country' => 'CH']],
                'max_account_value' => 25000,
                'tax_year'          => '2025',
            ]);

            expect($result['valid'])->toBeTrue();
        });

        it('rejects common fields when missing', function (): void {
            $result = $this->adapter->validateReport('CTR', []);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('entity_name is required.')
                ->and($result['errors'])->toContain('reporting_date is required.');
        });

        it('rejects unsupported report type', function (): void {
            $result = $this->adapter->validateReport('INVALID', ['entity_name' => 'Test']);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('Unsupported FinCEN report type: INVALID');
        });
    });

    describe('submitReport', function (): void {
        it('submits valid report successfully', function (): void {
            $result = $this->adapter->submitReport('CTR', [
                'entity_name'           => 'Test Corp',
                'reporting_date'        => '2026-01-15',
                'transaction_amount'    => 15000,
                'transaction_type'      => 'deposit',
                'financial_institution' => 'Test Bank',
                'conductor_info'        => ['name' => 'John Doe'],
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['reference'])->toStartWith('FINCEN-')
                ->and($result['errors'])->toBeEmpty()
                ->and($result['response']['jurisdiction'])->toBe('US')
                ->and($result['response']['sandbox'])->toBeTrue();
        });

        it('rejects unsupported report type on submit', function (): void {
            $result = $this->adapter->submitReport('INVALID', []);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('Unsupported report type: INVALID');
        });

        it('rejects invalid report data on submit', function (): void {
            $result = $this->adapter->submitReport('CTR', []);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->not->toBeEmpty();
        });
    });

    describe('checkStatus', function (): void {
        it('returns accepted status', function (): void {
            $result = $this->adapter->checkStatus('FINCEN-20260115-ABCD1234');

            expect($result['status'])->toBe('accepted')
                ->and($result['details']['adapter'])->toBe('FinCEN BSA E-Filing')
                ->and($result['details']['jurisdiction'])->toBe('US');
        });
    });
});
