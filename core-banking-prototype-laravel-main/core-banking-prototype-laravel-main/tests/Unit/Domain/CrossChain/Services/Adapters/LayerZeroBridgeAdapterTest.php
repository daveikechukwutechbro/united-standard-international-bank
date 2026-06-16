<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\LayerZeroBridgeAdapter;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->adapter = new LayerZeroBridgeAdapter();
});

describe('LayerZeroBridgeAdapter', function () {
    it('returns layerzero as provider', function () {
        expect($this->adapter->getProvider())->toBe(BridgeProvider::LAYERZERO);
    });

    it('estimates fees for L2-to-L2 transfers cheaper than L1', function () {
        $l2Fee = $this->adapter->estimateFee(
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            '1000.00',
        );

        $l1Fee = $this->adapter->estimateFee(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        expect(bccomp($l2Fee['fee'], $l1Fee['fee'], 8))->toBe(-1);
    });

    it('generates valid bridge quote', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::ARBITRUM,
            'USDC',
            '500.00',
        );

        expect($quote->getProvider())->toBe(BridgeProvider::LAYERZERO);
        expect($quote->estimatedTimeSeconds)->toBe(120);
        expect($quote->isExpired())->toBeFalse();
    });

    it('initiates bridge successfully', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::BASE,
            'USDT',
            '100.00',
        );

        $result = $this->adapter->initiateBridge($quote, '0xSender', '0xRecipient');

        expect($result['transaction_id'])->toStartWith('lz-tx-');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
    });

    it('supports EVM chains only (no Solana)', function () {
        expect($this->adapter->supportsRoute(CrossChainNetwork::POLYGON, CrossChainNetwork::ARBITRUM, 'USDC'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::SOLANA, 'USDC'))->toBeFalse();
    });

    it('returns bridge status', function () {
        $status = $this->adapter->getBridgeStatus('lz-tx-test');

        expect($status['status'])->toBe(BridgeStatus::COMPLETED);
        expect($status['confirmations'])->toBe(20);
    });
});
