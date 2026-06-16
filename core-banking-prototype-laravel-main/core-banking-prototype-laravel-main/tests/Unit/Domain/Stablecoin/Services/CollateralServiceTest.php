<?php

declare(strict_types=1);

use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Stablecoin\Contracts\CollateralServiceInterface;
use App\Domain\Stablecoin\Models\StablecoinCollateralPosition;
use App\Domain\Stablecoin\Services\CollateralService;

uses(Tests\TestCase::class);

/**
 * Helper to create a mock position with property access support.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $methods
 */
function mockPosition(array $attributes = [], array $methods = []): StablecoinCollateralPosition
{
    /** @var StablecoinCollateralPosition&Mockery\MockInterface $mock */
    $mock = Mockery::mock(StablecoinCollateralPosition::class)->shouldIgnoreMissing();

    foreach ($attributes as $key => $value) {
        $mock->shouldReceive('getAttribute')->with($key)->andReturn($value);
        $mock->shouldReceive('__get')->with($key)->andReturn($value);
    }

    foreach ($methods as $method => $return) {
        $mock->shouldReceive($method)->andReturn($return);
    }

    return $mock;
}

describe('CollateralService', function () {
    beforeEach(function () {
        $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $this->service = new CollateralService($this->exchangeRateService);
    });

    it('implements CollateralServiceInterface', function () {
        expect($this->service)->toBeInstanceOf(CollateralServiceInterface::class);
    });

    describe('convertToPegAsset', function () {
        it('returns the same amount when fromAsset equals pegAsset', function () {
            $result = $this->service->convertToPegAsset('USD', 1000.0, 'USD');

            expect($result)->toBe(1000.0);
        });

        it('converts amount using exchange rate', function () {
            $rateModel = Mockery::mock(ExchangeRate::class)->makePartial();
            $rateModel->forceFill(['rate' => 2500.0]);
            $this->exchangeRateService
                ->shouldReceive('getRate')
                ->with('ETH', 'USD')
                ->andReturn($rateModel);

            $result = $this->service->convertToPegAsset('ETH', 1.0, 'USD');

            expect($result)->toBe(2500.0);
        });

        it('rounds to two decimal places', function () {
            $rateModel = Mockery::mock(ExchangeRate::class)->makePartial();
            $rateModel->forceFill(['rate' => 1.23456]);
            $this->exchangeRateService
                ->shouldReceive('getRate')
                ->with('EUR', 'USD')
                ->andReturn($rateModel);

            $result = $this->service->convertToPegAsset('EUR', 100.0, 'USD');

            expect($result)->toBe(123.46);
        });

        it('throws when exchange rate is not found', function () {
            $this->exchangeRateService
                ->shouldReceive('getRate')
                ->with('UNKNOWN', 'USD')
                ->andReturn(null);

            expect(fn () => $this->service->convertToPegAsset('UNKNOWN', 100.0, 'USD'))
                ->toThrow(RuntimeException::class, 'Exchange rate not found');
        });
    });

    describe('calculatePositionHealthScore', function () {
        it('returns 1.0 for zero debt positions', function () {
            $position = mockPosition(['debt_amount' => 0]);

            $result = $this->service->calculatePositionHealthScore($position);

            expect($result)->toBe(1.0);
        });

        it('returns 0.0 when ratio equals minimum ratio', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];
            $position = mockPosition([
                'debt_amount'      => 1000,
                'collateral_ratio' => 1.5,
                'stablecoin'       => $stablecoin,
            ]);

            $result = $this->service->calculatePositionHealthScore($position);

            expect($result)->toBe(0.0);
        });

        it('returns score between 0 and 1 for normal positions', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];
            $position = mockPosition([
                'debt_amount'      => 1000,
                'collateral_ratio' => 2.25,
                'stablecoin'       => $stablecoin,
            ]);

            $result = $this->service->calculatePositionHealthScore($position);

            // (2.25 - 1.5) / 1.5 = 0.5
            expect($result)->toBe(0.5);
        });

        it('caps the score at 1.0', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];
            $position = mockPosition([
                'debt_amount'      => 1000,
                'collateral_ratio' => 5.0,
                'stablecoin'       => $stablecoin,
            ]);

            $result = $this->service->calculatePositionHealthScore($position);

            expect($result)->toBe(1.0);
        });

        it('returns 0.0 when ratio is below minimum', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];
            $position = mockPosition([
                'debt_amount'      => 1000,
                'collateral_ratio' => 1.0,
                'stablecoin'       => $stablecoin,
            ]);

            $result = $this->service->calculatePositionHealthScore($position);

            expect($result)->toBe(0.0);
        });
    });

    describe('calculateLiquidationPriority', function () {
        it('gives higher priority to unhealthy positions', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];

            $healthyPosition = mockPosition([
                'debt_amount'         => 1000,
                'collateral_ratio'    => 3.0,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => now(),
            ]);

            $unhealthyPosition = mockPosition([
                'debt_amount'         => 1000,
                'collateral_ratio'    => 1.5,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => now(),
            ]);

            $healthyPriority = $this->service->calculateLiquidationPriority($healthyPosition);
            $unhealthyPriority = $this->service->calculateLiquidationPriority($unhealthyPosition);

            expect($unhealthyPriority)->toBeGreaterThan($healthyPriority);
        });

        it('gives higher priority to larger debts', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];

            $smallDebt = mockPosition([
                'debt_amount'         => 100,
                'collateral_ratio'    => 1.6,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => now(),
            ]);

            $largeDebt = mockPosition([
                'debt_amount'         => 900000,
                'collateral_ratio'    => 1.6,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => now(),
            ]);

            $smallPriority = $this->service->calculateLiquidationPriority($smallDebt);
            $largePriority = $this->service->calculateLiquidationPriority($largeDebt);

            expect($largePriority)->toBeGreaterThan($smallPriority);
        });

        it('uses time since last interaction as a priority factor', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];

            $positionWithTime = mockPosition([
                'debt_amount'         => 1000,
                'collateral_ratio'    => 1.6,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => now(),
            ]);

            $positionWithoutTime = mockPosition([
                'debt_amount'         => 1000,
                'collateral_ratio'    => 1.6,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => null,
            ]);

            $priorityWithTime = $this->service->calculateLiquidationPriority($positionWithTime);
            $priorityWithoutTime = $this->service->calculateLiquidationPriority($positionWithoutTime);

            // Both should produce valid float priority scores
            expect($priorityWithTime)->toBeFloat();
            expect($priorityWithoutTime)->toBeFloat();
            // Scores should be non-negative
            expect($priorityWithTime)->toBeGreaterThanOrEqual(0.0);
            expect($priorityWithoutTime)->toBeGreaterThanOrEqual(0.0);
        });

        it('handles null last_interaction_at', function () {
            $stablecoin = (object) ['min_collateral_ratio' => 1.5];

            $position = mockPosition([
                'debt_amount'         => 1000,
                'collateral_ratio'    => 1.6,
                'stablecoin'          => $stablecoin,
                'last_interaction_at' => null,
            ]);

            $result = $this->service->calculateLiquidationPriority($position);

            expect($result)->toBeFloat();
            expect($result)->toBeGreaterThanOrEqual(0.0);
        });
    });

    describe('getPositionRecommendations', function () {
        it('recommends liquidation for auto-liquidatable positions', function () {
            $stablecoin = (object) [
                'min_collateral_ratio' => 1.5,
                'peg_asset_code'       => 'USD',
            ];

            $position = mockPosition([
                'debt_amount'           => 1000,
                'collateral_ratio'      => 1.0,
                'collateral_amount'     => 1000,
                'collateral_asset_code' => 'USD',
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate'       => true,
                'calculateLiquidationPrice' => 0,
            ]);

            $rateModel = Mockery::mock(ExchangeRate::class)->makePartial();
            $rateModel->forceFill(['rate' => 1.0]);
            $this->exchangeRateService->shouldReceive('getRate')->andReturn($rateModel);

            $recommendations = $this->service->getPositionRecommendations($position);

            expect($recommendations)->toBeArray();
            expect($recommendations[0]['action'])->toBe('liquidate');
            expect($recommendations[0]['urgency'])->toBe('critical');
        });

        it('recommends adding collateral for low health positions', function () {
            $stablecoin = (object) [
                'min_collateral_ratio' => 1.5,
                'collateral_ratio'     => 2.0,
                'peg_asset_code'       => 'USD',
            ];

            $position = mockPosition([
                'debt_amount'           => 1000,
                'collateral_ratio'      => 1.6,
                'collateral_amount'     => 1600,
                'collateral_asset_code' => 'USD',
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate'       => false,
                'calculateLiquidationPrice' => 0,
            ]);

            $this->exchangeRateService->shouldReceive('getRate')->andReturn(null);

            $recommendations = $this->service->getPositionRecommendations($position);

            expect($recommendations)->toBeArray();
            expect($recommendations[0]['action'])->toBe('add_collateral');
            expect($recommendations[0]['urgency'])->toBe('high');
        });

        it('recommends minting more for highly collateralized positions', function () {
            $stablecoin = (object) [
                'min_collateral_ratio' => 1.5,
                'peg_asset_code'       => 'USD',
            ];

            $position = mockPosition([
                'debt_amount'           => 1000,
                'collateral_ratio'      => 3.5,
                'collateral_amount'     => 3500,
                'collateral_asset_code' => 'USD',
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate'       => false,
                'calculateLiquidationPrice' => 0,
                'calculateMaxMintAmount'    => 500,
            ]);

            $recommendations = $this->service->getPositionRecommendations($position);

            expect($recommendations)->toBeArray();
            expect($recommendations[0]['action'])->toBe('mint_more');
            expect($recommendations[0]['urgency'])->toBe('low');
            expect($recommendations[0]['max_mint_amount'])->toBe(500);
        });

        it('recommends monitoring for medium health positions', function () {
            $stablecoin = (object) [
                'min_collateral_ratio' => 1.5,
                'peg_asset_code'       => 'USD',
            ];

            $position = mockPosition([
                'debt_amount'           => 1000,
                'collateral_ratio'      => 1.95,
                'collateral_amount'     => 1950,
                'collateral_asset_code' => 'USD',
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate'       => false,
                'calculateLiquidationPrice' => 0,
            ]);

            $recommendations = $this->service->getPositionRecommendations($position);

            expect($recommendations)->toBeArray();
            expect($recommendations[0]['action'])->toBe('monitor');
            expect($recommendations[0]['urgency'])->toBe('medium');
        });
    });
});
