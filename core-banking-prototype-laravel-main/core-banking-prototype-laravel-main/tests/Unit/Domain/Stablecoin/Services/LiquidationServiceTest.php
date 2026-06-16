<?php

declare(strict_types=1);

use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Stablecoin\Contracts\LiquidationServiceInterface;
use App\Domain\Stablecoin\Models\StablecoinCollateralPosition;
use App\Domain\Stablecoin\Services\CollateralService;
use App\Domain\Stablecoin\Services\LiquidationService;
use App\Domain\Wallet\Services\WalletService;

uses(Tests\TestCase::class);

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $methods
 */
function mockLiquidationPosition(array $attributes = [], array $methods = []): StablecoinCollateralPosition
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

describe('LiquidationService', function () {
    beforeEach(function () {
        $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $this->collateralService = Mockery::mock(CollateralService::class);
        $this->walletService = Mockery::mock(WalletService::class);

        $this->service = new LiquidationService(
            $this->exchangeRateService,
            $this->collateralService,
            $this->walletService
        );
    });

    it('implements LiquidationServiceInterface', function () {
        expect($this->service)->toBeInstanceOf(LiquidationServiceInterface::class);
    });

    describe('liquidatePosition', function () {
        it('throws when position is not eligible for liquidation', function () {
            $position = mockLiquidationPosition([], [
                'shouldAutoLiquidate' => false,
            ]);

            expect(fn () => $this->service->liquidatePosition($position))
                ->toThrow(RuntimeException::class, 'Position is not eligible for liquidation');
        });
    });

    describe('calculateLiquidationReward', function () {
        it('returns not eligible for healthy positions', function () {
            $position = mockLiquidationPosition([], [
                'shouldAutoLiquidate' => false,
            ]);

            $result = $this->service->calculateLiquidationReward($position);

            expect($result['eligible'])->toBeFalse();
            expect($result['reward'])->toBe(0);
            expect($result['penalty'])->toBe(0);
            expect($result['collateral_seized'])->toBe(0);
        });

        it('calculates correct reward for liquidatable position', function () {
            $stablecoin = (object) [
                'liquidation_penalty'  => 0.13,
                'min_collateral_ratio' => 1.5,
            ];

            $position = mockLiquidationPosition([
                'collateral_amount'     => 10000,
                'debt_amount'           => 8000,
                'collateral_asset_code' => 'ETH',
                'collateral_ratio'      => 1.2,
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate' => true,
            ]);

            $result = $this->service->calculateLiquidationReward($position);

            expect($result['eligible'])->toBeTrue();
            // Penalty: 10000 * 0.13 = 1300
            expect($result['penalty'])->toBe(1300);
            // Reward: 1300 * 0.5 = 650
            expect($result['reward'])->toBe(650);
            expect($result['collateral_seized'])->toBe(10000);
            expect($result['debt_amount'])->toBe(8000);
            expect($result['collateral_asset'])->toBe('ETH');
        });

        it('splits penalty 50/50 between liquidator and protocol', function () {
            $stablecoin = (object) [
                'liquidation_penalty'  => 0.10,
                'min_collateral_ratio' => 1.5,
            ];

            $position = mockLiquidationPosition([
                'collateral_amount'     => 20000,
                'debt_amount'           => 15000,
                'collateral_asset_code' => 'BTC',
                'collateral_ratio'      => 1.1,
                'stablecoin'            => $stablecoin,
            ], [
                'shouldAutoLiquidate' => true,
            ]);

            $result = $this->service->calculateLiquidationReward($position);

            // Penalty: 20000 * 0.10 = 2000
            // Liquidator: 2000 * 0.5 = 1000
            expect($result['reward'])->toBe(1000);
            expect($result['penalty'])->toBe(2000);
        });
    });

    describe('liquidateEligiblePositions', function () {
        it('returns empty results when no positions are eligible', function () {
            $this->collateralService
                ->shouldReceive('getPositionsForLiquidation')
                ->andReturn(collect());

            $result = $this->service->liquidateEligiblePositions();

            expect($result['liquidated_count'])->toBe(0);
            expect($result['failed_count'])->toBe(0);
            expect($result['total_liquidator_reward'])->toBe(0);
            expect($result['total_protocol_fees'])->toBe(0);
            expect($result['results'])->toBe([]);
        });
    });
});
