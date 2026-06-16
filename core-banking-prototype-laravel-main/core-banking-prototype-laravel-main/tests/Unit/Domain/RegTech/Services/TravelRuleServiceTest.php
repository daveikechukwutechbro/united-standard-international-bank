<?php

declare(strict_types=1);

use App\Domain\RegTech\Enums\Jurisdiction;
use App\Domain\RegTech\Services\JurisdictionConfigurationService;
use App\Domain\RegTech\Services\MicaComplianceService;
use App\Domain\RegTech\Services\TravelRuleService;

describe('TravelRuleService', function (): void {
    beforeEach(function (): void {
        $this->jurisdictionService = Mockery::mock(JurisdictionConfigurationService::class);
        $this->micaService = Mockery::mock(MicaComplianceService::class);

        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('USD')->andReturn(Jurisdiction::US)->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('EUR')->andReturn(Jurisdiction::EU)->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('GBP')->andReturn(Jurisdiction::UK)->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('SGD')->andReturn(Jurisdiction::SG)->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('XYZ')->andReturn(null)->byDefault();

        $this->jurisdictionService->shouldReceive('getMicaConfig')
            ->andReturn([
                'travel_rule' => [
                    'enabled'                   => true,
                    'threshold_eur'             => 1000,
                    'required_originator_info'  => ['name', 'address', 'account_number', 'doc_id'],
                    'required_beneficiary_info' => ['name', 'account_number'],
                ],
            ])
            ->byDefault();

        $this->micaService->shouldReceive('getComplianceStatus')
            ->andReturn(['enabled' => true, 'casp_authorized' => false])
            ->byDefault();

        $this->service = new TravelRuleService(
            $this->jurisdictionService,
            $this->micaService
        );
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('getThreshold', function (): void {
        it('returns $3000 for US', function (): void {
            expect($this->service->getThreshold(Jurisdiction::US))->toBe(3000.0);
        });

        it('returns EUR 1000 for EU', function (): void {
            expect($this->service->getThreshold(Jurisdiction::EU))->toBe(1000.0);
        });

        it('returns GBP 1000 for UK', function (): void {
            expect($this->service->getThreshold(Jurisdiction::UK))->toBe(1000.0);
        });

        it('returns SGD 1500 for SG', function (): void {
            expect($this->service->getThreshold(Jurisdiction::SG))->toBe(1500.0);
        });
    });

    describe('getThresholds', function (): void {
        it('returns thresholds for all jurisdictions', function (): void {
            $thresholds = $this->service->getThresholds();

            expect($thresholds)->toHaveKey('US')
                ->and($thresholds)->toHaveKey('EU')
                ->and($thresholds)->toHaveKey('UK')
                ->and($thresholds)->toHaveKey('SG')
                ->and($thresholds['US'])->toHaveKey('regulation')
                ->and($thresholds['EU'])->toHaveKey('regulation');
        });
    });

    describe('getRequiredData', function (): void {
        it('returns originator and beneficiary fields for US', function (): void {
            $data = $this->service->getRequiredData(Jurisdiction::US);

            expect($data)->toHaveKey('originator')
                ->and($data)->toHaveKey('beneficiary')
                ->and($data['originator'])->toContain('name')
                ->and($data['originator'])->toContain('account_number')
                ->and($data['beneficiary'])->toContain('name');
        });
    });

    describe('evaluate', function (): void {
        it('marks compliant for below-threshold transfers', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 500,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeTrue()
                ->and($result['jurisdiction'])->toBe('US')
                ->and($result['errors'])->toBeEmpty();
        });

        it('marks non-compliant for unknown currency', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 50000,
                'currency' => 'XYZ',
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['jurisdiction'])->toBe('unknown')
                ->and($result['errors'])->not->toBeEmpty();
        });

        it('detects missing originator data for above-threshold USD', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 5000,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['jurisdiction'])->toBe('US')
                ->and($result['errors'])->not->toBeEmpty();
        });

        it('passes with complete originator and beneficiary for USD', function (): void {
            $result = $this->service->evaluate([
                'amount'     => 5000,
                'currency'   => 'USD',
                'originator' => [
                    'name'           => 'John Doe',
                    'account_number' => 'ACC123',
                    'address'        => '123 Main St',
                ],
                'beneficiary' => [
                    'name'           => 'Jane Smith',
                    'account_number' => 'ACC456',
                ],
            ]);

            expect($result['compliant'])->toBeTrue()
                ->and($result['errors'])->toBeEmpty();
        });

        it('detects missing beneficiary data for EUR transfer', function (): void {
            $result = $this->service->evaluate([
                'amount'     => 2000,
                'currency'   => 'EUR',
                'originator' => [
                    'name'           => 'John Doe',
                    'address'        => '123 Main St',
                    'account_number' => 'ACC123',
                    'doc_id'         => 'PASS123',
                ],
                'beneficiary' => [],
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['errors'])->toContain('Missing beneficiary field: name');
        });

        it('returns required data structure', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 5000,
                'currency' => 'EUR',
            ]);

            expect($result['required_data'])->toHaveKey('originator')
                ->and($result['required_data'])->toHaveKey('beneficiary');
        });

        it('requires compliance at exactly the US threshold', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 3000,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['threshold'])->toBe(3000.0)
                ->and($result['errors'])->not->toBeEmpty();
        });

        it('marks compliant just below US threshold', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 2999.99,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeTrue()
                ->and($result['errors'])->toBeEmpty();
        });

        it('requires compliance at exactly the EU threshold', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 1000,
                'currency' => 'EUR',
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['threshold'])->toBe(1000.0);
        });

        it('handles transfer with null originator gracefully', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 5000,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeFalse()
                ->and($result['errors'])->toContain('Missing originator field: name')
                ->and($result['errors'])->toContain('Missing originator field: account_number');
        });

        it('handles transfer with zero amount', function (): void {
            $result = $this->service->evaluate([
                'amount'   => 0,
                'currency' => 'USD',
            ]);

            expect($result['compliant'])->toBeTrue()
                ->and($result['errors'])->toBeEmpty();
        });
    });

    describe('getComplianceSummary', function (): void {
        it('returns summary with all jurisdictions', function (): void {
            $summary = $this->service->getComplianceSummary();

            expect($summary)->toHaveKey('thresholds')
                ->and($summary)->toHaveKey('mica_status')
                ->and($summary)->toHaveKey('supported_jurisdictions')
                ->and($summary['supported_jurisdictions'])->toContain('US')
                ->and($summary['supported_jurisdictions'])->toContain('EU');
        });
    });
});
