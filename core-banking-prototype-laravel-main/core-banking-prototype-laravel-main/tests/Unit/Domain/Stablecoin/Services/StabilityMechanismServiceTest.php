<?php

declare(strict_types=1);

use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Stablecoin\Contracts\StabilityMechanismServiceInterface;
use App\Domain\Stablecoin\Models\Stablecoin;
use App\Domain\Stablecoin\Services\CollateralService;
use App\Domain\Stablecoin\Services\LiquidationService;
use App\Domain\Stablecoin\Services\StabilityMechanismService;

uses(Tests\TestCase::class);

describe('StabilityMechanismService', function () {
    beforeEach(function () {
        $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $this->collateralService = Mockery::mock(CollateralService::class);
        $this->liquidationService = Mockery::mock(LiquidationService::class);

        $this->service = new StabilityMechanismService(
            $this->exchangeRateService,
            $this->collateralService,
            $this->liquidationService
        );
    });

    it('implements StabilityMechanismServiceInterface', function () {
        expect($this->service)->toBeInstanceOf(StabilityMechanismServiceInterface::class);
    });

    it('can be constructed without liquidation service', function () {
        $service = new StabilityMechanismService(
            $this->exchangeRateService,
            $this->collateralService,
        );

        expect($service)->toBeInstanceOf(StabilityMechanismServiceInterface::class);
    });

    describe('calculateFeeAdjustment', function () {
        it('increases mint fee and decreases burn fee when price is above peg', function () {
            $currentFees = ['mint_fee' => 0.01, 'burn_fee' => 0.01];
            $deviation = 5.0; // Price above peg

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result['new_mint_fee'])->toBeGreaterThan(0.01);
            expect($result['new_burn_fee'])->toBeLessThan(0.01);
            expect($result)->toHaveKey('adjustment_reason');
            expect($result['adjustment_reason'])->toContain('above');
        });

        it('decreases mint fee and increases burn fee when price is below peg', function () {
            $currentFees = ['mint_fee' => 0.01, 'burn_fee' => 0.01];
            $deviation = -5.0; // Price below peg

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result['new_mint_fee'])->toBeLessThan(0.01);
            expect($result['new_burn_fee'])->toBeGreaterThan(0.01);
            expect($result['adjustment_reason'])->toContain('below');
        });

        it('does not change fees when deviation is zero', function () {
            $currentFees = ['mint_fee' => 0.01, 'burn_fee' => 0.01];
            $deviation = 0.0;

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result['new_mint_fee'])->toBe(0.01);
            expect($result['new_burn_fee'])->toBe(0.01);
        });

        it('caps mint fee at maximum of 0.1', function () {
            $currentFees = ['mint_fee' => 0.09, 'burn_fee' => 0.01];
            $deviation = 10.0; // Large positive deviation

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result['new_mint_fee'])->toBeLessThanOrEqual(0.1);
        });

        it('does not allow burn fee below zero', function () {
            $currentFees = ['mint_fee' => 0.01, 'burn_fee' => 0.001];
            $deviation = 10.0; // Large positive deviation

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result['new_burn_fee'])->toBeGreaterThanOrEqual(0.0);
        });

        it('uses default fees when current fees are missing', function () {
            $currentFees = [];
            $deviation = 5.0;

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            expect($result)->toHaveKey('new_mint_fee');
            expect($result)->toHaveKey('new_burn_fee');
            expect($result['new_mint_fee'])->toBeFloat();
            expect($result['new_burn_fee'])->toBeFloat();
        });

        it('rounds fee values to 6 decimal places', function () {
            $currentFees = ['mint_fee' => 0.01, 'burn_fee' => 0.01];
            $deviation = 3.0;

            $result = $this->service->calculateFeeAdjustment($deviation, $currentFees);

            // Check fees are properly rounded (no more than 6 decimals)
            $mintFeeStr = (string) $result['new_mint_fee'];
            $burnFeeStr = (string) $result['new_burn_fee'];

            if (str_contains($mintFeeStr, '.')) {
                expect(strlen(explode('.', $mintFeeStr)[1]))->toBeLessThanOrEqual(6);
            }
            if (str_contains($burnFeeStr, '.')) {
                expect(strlen(explode('.', $burnFeeStr)[1]))->toBeLessThanOrEqual(6);
            }
        });
    });

    describe('calculateSupplyIncentives', function () {
        it('recommends burning when deviation is negative', function () {
            $result = $this->service->calculateSupplyIncentives(-5.0, 1000000, 1000000);

            expect($result['recommended_action'])->toBe('burn');
            expect($result['burn_reward'])->toBeGreaterThan(0.0);
            expect($result['mint_reward'])->toBe(0);
        });

        it('recommends minting when deviation is positive', function () {
            $result = $this->service->calculateSupplyIncentives(5.0, 1000000, 1000000);

            expect($result['recommended_action'])->toBe('mint');
            expect($result['mint_reward'])->toBeGreaterThan(0.0);
            expect($result['burn_reward'])->toBe(0);
        });

        it('recommends no action when deviation is zero', function () {
            $result = $this->service->calculateSupplyIncentives(0.0, 1000000, 1000000);

            expect($result['recommended_action'])->toBe('none');
            expect($result['mint_reward'])->toBe(0);
            expect($result['burn_reward'])->toBe(0);
        });

        it('caps burn reward at 0.1', function () {
            $result = $this->service->calculateSupplyIncentives(-100.0, 1000000, 1000000);

            expect($result['burn_reward'])->toBeLessThanOrEqual(0.1);
        });

        it('caps mint reward at 0.1', function () {
            $result = $this->service->calculateSupplyIncentives(100.0, 1000000, 1000000);

            expect($result['mint_reward'])->toBeLessThanOrEqual(0.1);
        });

        it('scales incentives proportionally to deviation', function () {
            $smallDeviation = $this->service->calculateSupplyIncentives(-2.0, 1000000, 1000000);
            $largeDeviation = $this->service->calculateSupplyIncentives(-8.0, 1000000, 1000000);

            expect($largeDeviation['burn_reward'])->toBeGreaterThan($smallDeviation['burn_reward']);
        });
    });

    describe('executeStabilityMechanismForStablecoin', function () {
        it('throws for unknown stability mechanism', function () {
            $stablecoin = Mockery::mock(Stablecoin::class)->shouldIgnoreMissing();
            $stablecoin->shouldReceive('__get')->with('stability_mechanism')->andReturn('unknown_mechanism');

            expect(fn () => $this->service->executeStabilityMechanismForStablecoin($stablecoin))
                ->toThrow(InvalidArgumentException::class, 'Unknown stability mechanism');
        });
    });
});
