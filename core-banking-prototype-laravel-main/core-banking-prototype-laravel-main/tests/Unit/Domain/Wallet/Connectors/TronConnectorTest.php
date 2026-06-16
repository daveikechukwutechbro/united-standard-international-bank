<?php

declare(strict_types=1);

use App\Domain\Wallet\Connectors\TronConnector;
use App\Domain\Wallet\Contracts\BlockchainConnector;

describe('TronConnector', function (): void {
    it('implements BlockchainConnector interface', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');

        expect($connector)->toBeInstanceOf(BlockchainConnector::class);
    });

    it('returns mainnet as default chain ID', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');

        expect($connector->getChainId())->toBe('mainnet');
    });

    it('returns custom network as chain ID', function (): void {
        $connector = new TronConnector('https://api.shasta.trongrid.io', 'shasta');

        expect($connector->getChainId())->toBe('shasta');
    });

    it('validates correct Tron addresses', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');

        // Valid Tron addresses start with T and are 34 chars
        expect($connector->validateAddress('TJCnKsPa7y5okkXvQAidZBzqx3QyQ6sxMW'))->toBeTrue();
        expect($connector->validateAddress('TVj35bRJTLYqaRfyJjC7CmrChAoQagy8Yn'))->toBeTrue();
    });

    it('rejects invalid Tron addresses', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');

        // Doesn't start with T
        expect($connector->validateAddress('AJCnKsPa7y5okkXvQAidZBzqx3QyQ6sxMW'))->toBeFalse();
        // Too short
        expect($connector->validateAddress('T12345'))->toBeFalse();
        // Ethereum address format
        expect($connector->validateAddress('0x1234567890abcdef1234567890abcdef12345678'))->toBeFalse();
    });

    it('returns energy-based gas prices for Tron', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');
        $prices = $connector->getGasPrices();

        expect($prices)->toHaveKeys(['slow', 'standard', 'fast', 'instant']);
        expect($prices['standard'])->toBe('420');
    });

    it('generates address from public key', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');
        $address = $connector->generateAddress('testpubkey456');

        expect($address->chain)->toBe('tron');
        expect($address->publicKey)->toBe('testpubkey456');
        expect($address->metadata)->toHaveKey('network');
    });

    it('estimates gas with energy cost model', function (): void {
        $connector = new TronConnector('https://api.trongrid.io');

        $tx = new App\Domain\Wallet\ValueObjects\TransactionData(
            from: 'sender',
            to: 'receiver',
            value: '1000000',
            chain: 'tron',
        );

        $estimate = $connector->estimateGas($tx);

        expect($estimate->chain)->toBe('tron');
        expect($estimate->metadata['fee_type'])->toBe('energy');
        expect($estimate->metadata)->toHaveKey('energy_required');
    });
});
