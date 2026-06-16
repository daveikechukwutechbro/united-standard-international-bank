<?php

declare(strict_types=1);

use App\Domain\RegTech\Enums\Jurisdiction;
use App\Domain\RegTech\Services\JurisdictionConfigurationService;
use App\Domain\RegTech\Services\MifidReportingService;
use App\Domain\RegTech\Services\RegTechOrchestrationService;

describe('MifidReportingService', function (): void {
    beforeEach(function (): void {
        $this->jurisdictionService = Mockery::mock(JurisdictionConfigurationService::class);
        $this->orchestrationService = Mockery::mock(RegTechOrchestrationService::class);

        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('EUR')->andReturn(Jurisdiction::EU)
            ->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('GBP')->andReturn(Jurisdiction::UK)
            ->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('USD')->andReturn(Jurisdiction::US)
            ->byDefault();
        $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
            ->with('SGD')->andReturn(Jurisdiction::SG)
            ->byDefault();

        $this->jurisdictionService->shouldReceive('isMifidApplicable')
            ->with(Mockery::on(fn ($j) => $j === Jurisdiction::EU || $j === Jurisdiction::UK))
            ->andReturn(true)
            ->byDefault();
        $this->jurisdictionService->shouldReceive('isMifidApplicable')
            ->with(Mockery::on(fn ($j) => $j === Jurisdiction::US || $j === Jurisdiction::SG))
            ->andReturn(false)
            ->byDefault();

        $this->jurisdictionService->shouldReceive('getMifidConfig')
            ->andReturn([
                'arm_provider'              => 'internal',
                'best_execution_rts27'      => true,
                'best_execution_rts28'      => true,
                'instrument_reference_data' => [
                    'firds_enabled'    => true,
                    'anna_dsb_enabled' => true,
                ],
            ])
            ->byDefault();

        $this->orchestrationService->shouldReceive('isDemoMode')->andReturn(true)->byDefault();
        $this->orchestrationService->shouldReceive('submitReport')
            ->andReturn([
                'success'   => true,
                'reference' => 'DEMO-EU-MIFID-TEST123',
                'errors'    => [],
                'details'   => ['demo_mode' => true],
            ])
            ->byDefault();

        $this->service = new MifidReportingService(
            $this->jurisdictionService,
            $this->orchestrationService
        );
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('requiresReporting', function (): void {
        it('requires reporting for EUR transactions', function (): void {
            expect($this->service->requiresReporting(['price_currency' => 'EUR']))->toBeTrue();
        });

        it('requires reporting for GBP transactions', function (): void {
            expect($this->service->requiresReporting(['price_currency' => 'GBP']))->toBeTrue();
        });

        it('does not require reporting for USD transactions', function (): void {
            expect($this->service->requiresReporting(['price_currency' => 'USD']))->toBeFalse();
        });

        it('does not require reporting for SGD transactions', function (): void {
            expect($this->service->requiresReporting(['price_currency' => 'SGD']))->toBeFalse();
        });
    });

    describe('generateTransactionReport', function (): void {
        it('generates valid report', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => 150.50,
                'venue'               => 'XNAS',
                'price_currency'      => 'EUR',
                'buyer_id'            => 'CLIENT-001',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['report']['report_type'])->toBe('MiFID_Transaction')
                ->and($result['report']['instrument_id'])->toBe('US0378331005')
                ->and($result['report']['quantity'])->toBe(100.0)
                ->and($result['report']['price'])->toBe(150.50)
                ->and($result['report'])->toHaveKey('reporting_deadline')
                ->and($result['errors'])->toBeEmpty();
        });

        it('rejects missing instrument_id', function (): void {
            $result = $this->service->generateTransactionReport([
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => 150.50,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('instrument_id (ISIN) is required.');
        });

        it('rejects missing executing entity', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id' => 'US0378331005',
                'quantity'      => 100,
                'price'         => 150.50,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('executing_entity_id (LEI) is required.');
        });

        it('rejects zero quantity', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 0,
                'price'               => 150.50,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('quantity must be a positive number.');
        });

        it('rejects zero price', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => 0,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('price must be a positive number.');
        });
    });

    describe('submitTransactionReport', function (): void {
        it('submits valid report', function (): void {
            $result = $this->service->submitTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => 150.50,
                'price_currency'      => 'EUR',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['reference'])->not->toBeNull();
        });

        it('rejects invalid transaction data', function (): void {
            $result = $this->service->submitTransactionReport([]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->not->toBeEmpty();
        });
    });

    describe('getBestExecutionAnalysis', function (): void {
        it('returns analysis with venue breakdown', function (): void {
            $result = $this->service->getBestExecutionAnalysis();

            expect($result)->toHaveKey('rts27_enabled')
                ->and($result)->toHaveKey('rts28_enabled')
                ->and($result)->toHaveKey('analysis')
                ->and($result['analysis'])->toHaveKey('venue_breakdown')
                ->and($result['analysis'])->toHaveKey('execution_quality')
                ->and($result['analysis']['venue_breakdown'])->not->toBeEmpty();
        });
    });

    describe('getInstrumentReferenceDataStatus', function (): void {
        it('returns instrument reference status', function (): void {
            $result = $this->service->getInstrumentReferenceDataStatus();

            expect($result)->toHaveKey('firds_enabled')
                ->and($result)->toHaveKey('anna_dsb_enabled')
                ->and($result['status'])->toBe('healthy');
        });
    });

    describe('generateTransactionReport - null jurisdiction handling', function (): void {
        it('defaults jurisdiction to EU when unresolvable', function (): void {
            $this->jurisdictionService->shouldReceive('getJurisdictionByCurrency')
                ->with('XXX')->andReturn(null);

            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => 150.50,
                'currency'            => 'XXX',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['report']['jurisdiction'])->toBe('EU');
        });
    });

    describe('validateTransactionData - edge cases', function (): void {
        it('rejects negative quantity', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => -10,
                'price'               => 150.50,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('quantity must be a positive number.');
        });

        it('rejects negative price', function (): void {
            $result = $this->service->generateTransactionReport([
                'instrument_id'       => 'US0378331005',
                'executing_entity_id' => '529900T8BM49AURSDO55',
                'quantity'            => 100,
                'price'               => -50.0,
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['errors'])->toContain('price must be a positive number.');
        });

        it('reports all missing fields at once', function (): void {
            $result = $this->service->generateTransactionReport([]);

            expect($result['success'])->toBeFalse()
                ->and(count($result['errors']))->toBeGreaterThanOrEqual(4);
        });
    });
});
