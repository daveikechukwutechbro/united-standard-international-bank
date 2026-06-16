<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\AxelarBridgeAdapter;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->adapter = new AxelarBridgeAdapter();
});

describe('AxelarBridgeAdapter', function () {
    it('returns axelar as provider', function () {
        expect($this->adapter->getProvider())->toBe(BridgeProvider::AXELAR);
    });

    it('estimates higher fees for Ethereum-involved routes', function () {
        $ethFee = $this->adapter->estimateFee(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        $l2Fee = $this->adapter->estimateFee(
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            '1000.00',
        );

        expect(bccomp($ethFee['fee'], $l2Fee['fee'], 8))->toBe(1);
    });

    it('generates valid bridge quote with Axelar timing', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::BSC,
            'USDT',
            '2000.00',
        );

        expect($quote->getProvider())->toBe(BridgeProvider::AXELAR);
        expect($quote->estimatedTimeSeconds)->toBe(180);
    });

    it('initiates bridge and returns Axelar transaction', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ARBITRUM,
            CrossChainNetwork::OPTIMISM,
            'WETH',
            '1.50',
        );

        $result = $this->adapter->initiateBridge($quote, '0xSender', '0xRecipient');

        expect($result['transaction_id'])->toStartWith('axelar-tx-');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
    });

    it('supports EVM chains only', function () {
        expect($this->adapter->supportsRoute(CrossChainNetwork::POLYGON, CrossChainNetwork::BSC, 'USDC'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::SOLANA, 'USDC'))->toBeFalse();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::BITCOIN, 'BTC'))->toBeFalse();
    });

    it('supports DAI and WBTC tokens', function () {
        $routes = $this->adapter->getSupportedRoutes();
        $tokens = array_unique(array_map(fn ($r) => $r->token, $routes));

        expect($tokens)->toContain('DAI');
        expect($tokens)->toContain('WBTC');
    });
});
