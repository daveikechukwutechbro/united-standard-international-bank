<?php

declare(strict_types=1);

use App\Domain\RegTech\Enums\Jurisdiction;
use App\Domain\RegTech\Services\JurisdictionConfigurationService;
use App\Domain\RegTech\Services\MicaComplianceService;

describe('MicaComplianceService', function (): void {
    beforeEach(function (): void {
        $this->jurisdictionService = Mockery::mock(JurisdictionConfigurationService::class);

        $this->jurisdictionService->shouldReceive('isMicaApplicable')
            ->with(Jurisdiction::EU)
            ->andReturn(true)
            ->byDefault();

        $this->jurisdictionService->shouldReceive('getMicaConfig')
            ->andReturn([
                'enabled'            => true,
                'casp_authorization' => false,
                'travel_rule'        => [
                    'enabled'                   => true,
                    'threshold_eur'             => 1000,
                    'required_originator_info'  => ['name', 'address', 'account_number', 'doc_id'],
                    'required_beneficiary_info' => ['name', 'account_number'],
                ],
                'whitepaper_requirements' => [
                    'max_pages'         => 40,
                    'required_sections' => [
                        'issuer_info',
                        'project_description',
                        'token_mechanics',
                        'rights_obligations',
                        'risks',
                        'technology',
                        'environmental_impact',
                    ],
                ],
                'reserve_management' => [
                    'art_reserve_ratio'  => 1.0,
                    'emt_reserve_ratio'  => 1.0,
                    'audit_frequency'    => 'monthly',
                    'custodian_required' => true,
                ],
            ])
            ->byDefault();

        $this->service = new MicaComplianceService($this->jurisdictionService);
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('getComplianceStatus', function (): void {
        it('returns full compliance status', function (): void {
            $status = $this->service->getComplianceStatus();

            expect($status)->toHaveKey('enabled')
                ->and($status)->toHaveKey('casp_authorized')
                ->and($status)->toHaveKey('travel_rule')
                ->and($status)->toHaveKey('reserve_management')
                ->and($status)->toHaveKey('whitepaper')
                ->and($status['travel_rule'])->toHaveKey('threshold_eur')
                ->and($status['reserve_management'])->toHaveKey('art_reserve_ratio');
        });
    });

    describe('validateWhitepaper', function (): void {
        it('validates complete whitepaper', function (): void {
            $result = $this->service->validateWhitepaper([
                'issuer_legal_name' => 'FinAegis Token Corp',
                'publication_date'  => '2026-01-15',
                'page_count'        => 30,
                'sections'          => [
                    'issuer_info'          => 'Company description...',
                    'project_description'  => 'Project goals...',
                    'token_mechanics'      => 'Token supply...',
                    'rights_obligations'   => 'Holder rights...',
                    'risks'                => 'Risk factors...',
                    'technology'           => 'Blockchain tech...',
                    'environmental_impact' => 'Energy usage...',
                ],
            ]);

            expect($result['valid'])->toBeTrue()
                ->and($result['errors'])->toBeEmpty();
        });

        it('rejects whitepaper with missing sections', function (): void {
            $result = $this->service->validateWhitepaper([
                'issuer_legal_name' => 'Test Corp',
                'page_count'        => 20,
                'sections'          => [
                    'issuer_info' => 'Some info',
                ],
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->not->toBeEmpty();
        });

        it('rejects whitepaper exceeding page limit', function (): void {
            $result = $this->service->validateWhitepaper([
                'issuer_legal_name' => 'Test Corp',
                'page_count'        => 50,
                'sections'          => [
                    'issuer_info'          => 'Info',
                    'project_description'  => 'Description',
                    'token_mechanics'      => 'Mechanics',
                    'rights_obligations'   => 'Rights',
                    'risks'                => 'Risks',
                    'technology'           => 'Tech',
                    'environmental_impact' => 'Impact',
                ],
            ]);

            expect($result['valid'])->toBeFalse()
                ->and($result['errors'])->toContain('Whitepaper exceeds maximum page count (50/40).');
        });

        it('warns about missing issuer name', function (): void {
            $result = $this->service->validateWhitepaper([
                'page_count' => 20,
                'sections'   => [],
            ]);

            expect($result['errors'])->toContain('issuer_legal_name is required.');
        });

        it('warns about unusually short whitepaper', function (): void {
            $result = $this->service->validateWhitepaper([
                'issuer_legal_name' => 'Test Corp',
                'page_count'        => 3,
                'sections'          => [
                    'issuer_info'          => 'Info',
                    'project_description'  => 'Description',
                    'token_mechanics'      => 'Mechanics',
                    'rights_obligations'   => 'Rights',
                    'risks'                => 'Risks',
                    'technology'           => 'Tech',
                    'environmental_impact' => 'Impact',
                ],
            ]);

            expect($result['warnings'])->toContain('Whitepaper appears unusually short for regulatory compliance.');
        });
    });

    describe('getReserveStatus', function (): void {
        it('returns reserve management status', function (): void {
            $status = $this->service->getReserveStatus();

            expect($status)->toHaveKey('art_reserve_ratio')
                ->and($status)->toHaveKey('emt_reserve_ratio')
                ->and($status)->toHaveKey('current_reserves')
                ->and($status['current_reserves']['compliant'])->toBeTrue();
        });
    });

    describe('checkTravelRuleRequirement', function (): void {
        it('requires travel rule above threshold', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'   => 1500,
                'currency' => 'EUR',
            ]);

            expect($result['required'])->toBeTrue()
                ->and($result['threshold'])->toBe(1000.0)
                ->and($result['amount'])->toBe(1500.0);
        });

        it('does not require travel rule below threshold', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'   => 500,
                'currency' => 'EUR',
            ]);

            expect($result['required'])->toBeFalse();
        });

        it('reports missing originator fields', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'      => 2000,
                'currency'    => 'EUR',
                'originator'  => ['name' => 'John Doe'],
                'beneficiary' => [],
            ]);

            expect($result['required'])->toBeTrue()
                ->and($result['missing_fields'])->not->toBeEmpty();
        });

        it('reports no missing fields when all provided', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'     => 2000,
                'currency'   => 'EUR',
                'originator' => [
                    'name'           => 'John Doe',
                    'address'        => '123 Main St',
                    'account_number' => 'ACC123',
                    'doc_id'         => 'PASS123',
                ],
                'beneficiary' => [
                    'name'           => 'Jane Smith',
                    'account_number' => 'ACC456',
                ],
            ]);

            expect($result['required'])->toBeTrue()
                ->and($result['missing_fields'])->toBeEmpty();
        });

        it('requires travel rule at exactly the threshold', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'   => 1000,
                'currency' => 'EUR',
            ]);

            expect($result['required'])->toBeTrue()
                ->and($result['threshold'])->toBe(1000.0);
        });

        it('does not require travel rule just below threshold', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'   => 999.99,
                'currency' => 'EUR',
            ]);

            expect($result['required'])->toBeFalse();
        });

        it('handles missing originator key gracefully', function (): void {
            $result = $this->service->checkTravelRuleRequirement([
                'amount'   => 2000,
                'currency' => 'EUR',
            ]);

            expect($result['required'])->toBeTrue()
                ->and($result['missing_fields'])->not->toBeEmpty();
        });
    });
});
