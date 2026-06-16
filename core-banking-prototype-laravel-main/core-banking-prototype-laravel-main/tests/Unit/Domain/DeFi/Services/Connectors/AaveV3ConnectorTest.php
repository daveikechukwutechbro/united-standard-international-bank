<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\AaveV3Connector;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->connector = new AaveV3Connector(new AbiEncoder(), new EthRpcClient());
});

describe('AaveV3Connector', function () {
    it('returns aave_v3 as protocol', function () {
        expect($this->connector->getProtocol())->toBe(DeFiProtocol::AAVE_V3);
    });

    it('supplies assets and returns atoken info', function () {
        $result = $this->connector->supply(CrossChainNetwork::ETHEREUM, 'USDC', '1000.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['supplied_amount'])->toBe('1000.00');
        expect($result['atoken_received'])->toBe('1000.00');
    });

    it('borrows assets and returns health factor', function () {
        $result = $this->connector->borrow(CrossChainNetwork::ETHEREUM, 'WETH', '2.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['borrowed_amount'])->toBe('2.00');
        expect((float) $result['health_factor'])->toBeGreaterThan(1.0);
    });

    it('repays borrowed assets', function () {
        $result = $this->connector->repay(CrossChainNetwork::POLYGON, 'USDC', '500.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['repaid_amount'])->toBe('500.00');
    });

    it('withdraws supplied assets', function () {
        $result = $this->connector->withdraw(CrossChainNetwork::ARBITRUM, 'DAI', '1000.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['withdrawn_amount'])->toBe('1000.00');
    });

    it('returns lending markets for supported chains', function () {
        $markets = $this->connector->getMarkets(CrossChainNetwork::ETHEREUM);

        expect($markets)->not->toBeEmpty();
        expect($markets[0])->toHaveKey('token');
        expect($markets[0])->toHaveKey('supply_apy');
        expect($markets[0])->toHaveKey('borrow_apy');
        expect($markets[0])->toHaveKey('ltv');
    });

    it('returns empty markets for unsupported chains', function () {
        $markets = $this->connector->getMarkets(CrossChainNetwork::BITCOIN);

        expect($markets)->toBeEmpty();
    });

    it('returns user positions with health factor', function () {
        $positions = $this->connector->getUserPositions(CrossChainNetwork::ETHEREUM, '0xWallet');

        expect($positions)->toHaveKey('supplies');
        expect($positions)->toHaveKey('borrows');
        expect($positions)->toHaveKey('health_factor');
        expect($positions)->toHaveKey('net_apy');
    });

    it('returns USDC market with realistic APY values', function () {
        $markets = $this->connector->getMarkets(CrossChainNetwork::ETHEREUM);
        $usdcMarket = collect($markets)->firstWhere('token', 'USDC');

        expect($usdcMarket)->not->toBeNull();
        expect((float) $usdcMarket['supply_apy'])->toBeLessThan((float) $usdcMarket['borrow_apy']);
    });
});
