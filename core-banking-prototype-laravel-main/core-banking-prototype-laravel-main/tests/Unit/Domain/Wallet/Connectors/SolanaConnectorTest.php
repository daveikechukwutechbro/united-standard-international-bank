<?php

declare(strict_types=1);

use App\Domain\Wallet\Connectors\SolanaConnector;
use App\Domain\Wallet\Contracts\BlockchainConnector;

describe('SolanaConnector', function (): void {
    it('implements BlockchainConnector interface', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');

        expect($connector)->toBeInstanceOf(BlockchainConnector::class);
    });

    it('returns mainnet-beta as default chain ID', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');

        expect($connector->getChainId())->toBe('mainnet-beta');
    });

    it('returns custom network as chain ID', function (): void {
        $connector = new SolanaConnector('https://api.devnet.solana.com', 'devnet');

        expect($connector->getChainId())->toBe('devnet');
    });

    it('validates correct Solana addresses', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');

        // Valid base58 Solana addresses
        expect($connector->validateAddress('11111111111111111111111111111111'))->toBeTrue();
        expect($connector->validateAddress('So11111111111111111111111111111112'))->toBeTrue();
        expect($connector->validateAddress('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'))->toBeTrue();
    });

    it('rejects invalid Solana addresses', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');

        // Too short
        expect($connector->validateAddress('short'))->toBeFalse();
        // Contains invalid characters (0, O, I, l)
        expect($connector->validateAddress('0x' . str_repeat('a', 40)))->toBeFalse();
        // Ethereum address format
        expect($connector->validateAddress('0x1234567890abcdef1234567890abcdef12345678'))->toBeFalse();
    });

    it('returns fixed gas prices for Solana', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');
        $prices = $connector->getGasPrices();

        expect($prices)->toHaveKeys(['slow', 'standard', 'fast', 'instant']);
        // Solana fees are fixed per signature
        expect($prices['slow'])->toBe('5000');
        expect($prices['standard'])->toBe('5000');
    });

    it('generates address from public key', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');
        $address = $connector->generateAddress('testpubkey123');

        expect($address->chain)->toBe('solana');
        expect($address->publicKey)->toBe('testpubkey123');
        expect($address->address)->toBe('testpubkey123');
        expect($address->metadata)->toHaveKey('network');
    });

    it('estimates gas with fixed per-signature fee', function (): void {
        $connector = new SolanaConnector('https://api.mainnet-beta.solana.com');

        $tx = new App\Domain\Wallet\ValueObjects\TransactionData(
            from: 'sender',
            to: 'receiver',
            value: '1000000',
            chain: 'solana',
        );

        $estimate = $connector->estimateGas($tx);

        expect($estimate->chain)->toBe('solana');
        expect($estimate->estimatedCost)->toBe('5000');
        expect($estimate->metadata['fee_type'])->toBe('per_signature');
    });
});
