<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\DeFi\Contracts\LendingProtocolInterface;
use App\Domain\DeFi\Contracts\LiquidStakingInterface;
use App\Domain\DeFi\Services\Connectors\AaveV3Connector;
use App\Domain\DeFi\Services\Connectors\CurveConnector;
use App\Domain\DeFi\Services\Connectors\DemoSwapConnector;
use App\Domain\DeFi\Services\Connectors\LidoConnector;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Domain\DeFi\Services\DeFiPortfolioService;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;
use App\Domain\DeFi\Services\FlashLoanService;
use App\Domain\DeFi\Services\SwapAggregatorService;
use App\Domain\DeFi\Services\SwapRouterService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the DeFi domain (protocol connectors & position tracking).
 */
class DeFiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/defi.php',
            'defi',
        );

        $this->app->singleton(SwapAggregatorService::class, function ($app) {
            $aggregator = new SwapAggregatorService();

            // Always register demo connector
            $aggregator->registerConnector(new DemoSwapConnector());

            // Register production connectors when enabled
            if (config('defi.uniswap.enabled', false)) {
                $aggregator->registerConnector($app->make(UniswapV3Connector::class));
            }

            if (config('defi.curve.enabled', false)) {
                $aggregator->registerConnector(new CurveConnector());
            }

            return $aggregator;
        });

        $this->app->singleton(DeFiPositionTrackerService::class);

        $this->app->singleton(DeFiPortfolioService::class, function ($app) {
            return new DeFiPortfolioService(
                $app->make(DeFiPositionTrackerService::class),
            );
        });

        // Lending protocol (Aave V3)
        $this->app->bind(LendingProtocolInterface::class, AaveV3Connector::class);

        // Liquid staking (Lido)
        $this->app->bind(LiquidStakingInterface::class, LidoConnector::class);

        $this->app->singleton(SwapRouterService::class, function ($app) {
            return new SwapRouterService(
                $app->make(SwapAggregatorService::class),
            );
        });

        $this->app->singleton(FlashLoanService::class, function ($app) {
            return new FlashLoanService(
                $app->make(LendingProtocolInterface::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
