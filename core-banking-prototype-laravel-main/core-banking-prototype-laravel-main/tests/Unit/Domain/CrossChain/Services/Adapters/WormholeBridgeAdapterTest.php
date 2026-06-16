<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\WormholeBridgeAdapter;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->adapter = new WormholeBridgeAdapter(new AbiEncoder(), new EthRpcClient());
});

describe('WormholeBridgeAdapter', function () {
    it('returns wormhole as provider', function () {
        expect($this->adapter->getProvider())->toBe(BridgeProvider::WORMHOLE);
    });

    it('estimates fees with base + relayer fee', function () {
        $fee = $this->adapter->estimateFee(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($fee['fee'])->not->toBe('0');
        expect($fee['fee_currency'])->toBe('USD');
        expect($fee['estimated_time'])->toBe(900);
    });

    it('generates valid bridge quote', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect($quote->getProvider())->toBe(BridgeProvider::WORMHOLE);
        expect($quote->inputAmount)->toBe('1000.00');
        expect(bccomp($quote->outputAmount, '0', 8))->toBe(1);
        expect($quote->isExpired())->toBeFalse();
    });

    it('initiates bridge and returns transaction id', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'WETH',
            '5.00',
        );

        $result = $this->adapter->initiateBridge($quote, '0xSender', '0xRecipient');

        expect($result['transaction_id'])->toStartWith('wormhole-tx-');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
        expect($result['source_tx_hash'])->toStartWith('0x');
    });

    it('supports EVM and Solana routes', function () {
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::POLYGON, 'USDC'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::SOLANA, 'USDC'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::BITCOIN, 'USDC'))->toBeFalse();
    });

    it('returns supported routes for all chain pairs', function () {
        $routes = $this->adapter->getSupportedRoutes();

        expect($routes)->not->toBeEmpty();
        expect($routes[0]->provider)->toBe(BridgeProvider::WORMHOLE);
    });
});
