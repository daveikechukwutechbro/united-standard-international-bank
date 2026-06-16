<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\CrossChainAssetRegistryService;

beforeEach(function () {
    $this->service = new CrossChainAssetRegistryService();
});

describe('CrossChainAssetRegistryService', function () {
    it('returns token address for known token and chain', function () {
        $address = $this->service->getTokenAddress('USDC', CrossChainNetwork::ETHEREUM);

        expect($address)->toBe('0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48');
    });

    it('returns null for unknown token', function () {
        $address = $this->service->getTokenAddress('UNKNOWN', CrossChainNetwork::ETHEREUM);

        expect($address)->toBeNull();
    });

    it('returns null for unsupported chain', function () {
        $address = $this->service->getTokenAddress('USDC', CrossChainNetwork::BITCOIN);

        expect($address)->toBeNull();
    });

    it('maps token address across chains', function () {
        $polygonAddress = $this->service->mapTokenAddress(
            '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
        );

        expect($polygonAddress)->toBe('0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359');
    });

    it('gets canonical token symbol from address', function () {
        $symbol = $this->service->getCanonicalToken(
            '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            CrossChainNetwork::ETHEREUM,
        );

        expect($symbol)->toBe('USDC');
    });

    it('gets supported tokens for a chain', function () {
        $tokens = $this->service->getSupportedTokens(CrossChainNetwork::ETHEREUM);

        expect($tokens)->toHaveKey('USDC');
        expect($tokens)->toHaveKey('USDT');
        expect($tokens)->toHaveKey('WETH');
    });

    it('registers custom tokens', function () {
        $this->service->registerToken('LINK', CrossChainNetwork::ETHEREUM, '0x514910771AF9Ca656af840dff83E8264EcF986CA');

        $address = $this->service->getTokenAddress('LINK', CrossChainNetwork::ETHEREUM);

        expect($address)->toBe('0x514910771AF9Ca656af840dff83E8264EcF986CA');
    });

    it('checks if token is supported on chain', function () {
        expect($this->service->isTokenSupported('USDC', CrossChainNetwork::ETHEREUM))->toBeTrue();
        expect($this->service->isTokenSupported('USDC', CrossChainNetwork::BITCOIN))->toBeFalse();
    });
});
