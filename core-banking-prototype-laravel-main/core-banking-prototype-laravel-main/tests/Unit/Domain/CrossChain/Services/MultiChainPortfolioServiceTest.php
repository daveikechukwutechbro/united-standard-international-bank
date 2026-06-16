<?php

declare(strict_types=1);

use App\Domain\CrossChain\Services\BridgeTransactionTracker;
use App\Domain\CrossChain\Services\CrossChainAssetRegistryService;
use App\Domain\CrossChain\Services\MultiChainPortfolioService;
use App\Domain\DeFi\Services\DeFiPortfolioService;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;

uses(Tests\TestCase::class);

beforeEach(function () {
    $positionTracker = new DeFiPositionTrackerService();
    $defiPortfolio = new DeFiPortfolioService($positionTracker);
    $bridgeTracker = new BridgeTransactionTracker();
    $assetRegistry = new CrossChainAssetRegistryService();

    $this->service = new MultiChainPortfolioService(
        $defiPortfolio,
        $bridgeTracker,
        $assetRegistry,
    );
});

describe('MultiChainPortfolioService', function () {
    it('returns full portfolio with all sections', function () {
        $portfolio = $this->service->getFullPortfolio('0xPortfolioWallet');

        expect($portfolio)->toHaveKeys([
            'wallet_address', 'total_value_usd', 'balances',
            'defi_summary', 'bridge_history', 'chains_active',
        ]);
        expect($portfolio['wallet_address'])->toBe('0xPortfolioWallet');
    });

    it('returns balances across multiple chains', function () {
        $balances = $this->service->getBalancesAcrossChains('0xBalanceWallet');

        expect($balances)->not->toBeEmpty();

        $chains = array_unique(array_map(fn ($b) => $b->chain->value, $balances));
        expect(count($chains))->toBeGreaterThan(1);
    });

    it('calculates total portfolio value in USD', function () {
        $portfolio = $this->service->getFullPortfolio('0xValueWallet');

        expect((float) $portfolio['total_value_usd'])->toBeGreaterThan(0);
    });

    it('identifies active chains', function () {
        $portfolio = $this->service->getFullPortfolio('0xActiveWallet');

        expect($portfolio['chains_active'])->not->toBeEmpty();
        expect($portfolio['chains_active'])->toContain('ethereum');
    });

    it('returns value breakdown by chain', function () {
        $byChain = $this->service->getValueByChain('0xChainWallet');

        expect($byChain)->not->toBeEmpty();
        expect($byChain)->toHaveKey('ethereum');
        expect($byChain['ethereum'])->toHaveKeys(['chain', 'total_value_usd', 'token_count']);
    });

    it('includes bridge history summary', function () {
        $portfolio = $this->service->getFullPortfolio('0xHistoryWallet');

        expect($portfolio['bridge_history'])->toHaveKeys(['total', 'pending', 'completed']);
    });
});
