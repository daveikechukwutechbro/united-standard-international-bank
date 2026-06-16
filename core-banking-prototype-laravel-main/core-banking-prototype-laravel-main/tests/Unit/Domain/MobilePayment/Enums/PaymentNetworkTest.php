<?php

declare(strict_types=1);

use App\Domain\MobilePayment\Enums\PaymentNetwork;

describe('PaymentNetwork Enum', function (): void {
    it('has Solana, Tron, and EVM chains', function (): void {
        $networks = PaymentNetwork::cases();

        expect($networks)->toHaveCount(6);
        expect(PaymentNetwork::SOLANA->value)->toBe('SOLANA');
        expect(PaymentNetwork::TRON->value)->toBe('TRON');
        expect(PaymentNetwork::POLYGON->value)->toBe('polygon');
        expect(PaymentNetwork::BASE->value)->toBe('base');
        expect(PaymentNetwork::ARBITRUM->value)->toBe('arbitrum');
        expect(PaymentNetwork::ETHEREUM->value)->toBe('ethereum');
    });

    it('returns correct labels', function (): void {
        expect(PaymentNetwork::SOLANA->label())->toBe('Solana');
        expect(PaymentNetwork::TRON->label())->toBe('Tron');
        expect(PaymentNetwork::POLYGON->label())->toBe('Polygon');
        expect(PaymentNetwork::BASE->label())->toBe('Base');
        expect(PaymentNetwork::ARBITRUM->label())->toBe('Arbitrum');
        expect(PaymentNetwork::ETHEREUM->label())->toBe('Ethereum');
    });

    it('returns correct native assets', function (): void {
        expect(PaymentNetwork::SOLANA->nativeAsset())->toBe('SOL');
        expect(PaymentNetwork::TRON->nativeAsset())->toBe('TRX');
        expect(PaymentNetwork::POLYGON->nativeAsset())->toBe('MATIC');
        expect(PaymentNetwork::BASE->nativeAsset())->toBe('ETH');
        expect(PaymentNetwork::ARBITRUM->nativeAsset())->toBe('ETH');
        expect(PaymentNetwork::ETHEREUM->nativeAsset())->toBe('ETH');
    });

    it('returns explorer URLs', function (): void {
        expect(PaymentNetwork::SOLANA->explorerUrl('abc123'))
            ->toBe('https://solscan.io/tx/abc123');
        expect(PaymentNetwork::TRON->explorerUrl('def456'))
            ->toBe('https://tronscan.org/#/transaction/def456');
        expect(PaymentNetwork::POLYGON->explorerUrl('0xabc'))
            ->toBe('https://polygonscan.com/tx/0xabc');
        expect(PaymentNetwork::BASE->explorerUrl('0xabc'))
            ->toBe('https://basescan.org/tx/0xabc');
        expect(PaymentNetwork::ARBITRUM->explorerUrl('0xabc'))
            ->toBe('https://arbiscan.io/tx/0xabc');
        expect(PaymentNetwork::ETHEREUM->explorerUrl('0xabc'))
            ->toBe('https://etherscan.io/tx/0xabc');
    });

    it('returns required confirmations', function (): void {
        foreach (PaymentNetwork::cases() as $network) {
            expect($network->requiredConfirmations())->toBeGreaterThan(0);
        }
    });

    it('returns address patterns', function (): void {
        foreach (PaymentNetwork::cases() as $network) {
            expect($network->addressPattern())->toBeString();
        }
    });

    it('returns all values as array', function (): void {
        $values = PaymentNetwork::values();
        expect($values)->toBe(['SOLANA', 'TRON', 'polygon', 'base', 'arbitrum', 'ethereum']);
    });

    it('returns average gas cost', function (): void {
        foreach (PaymentNetwork::cases() as $network) {
            expect($network->averageGasCostUsd())->toBeFloat();
        }
    });

    it('identifies EVM chains correctly', function (): void {
        expect(PaymentNetwork::POLYGON->isEvm())->toBeTrue();
        expect(PaymentNetwork::BASE->isEvm())->toBeTrue();
        expect(PaymentNetwork::ARBITRUM->isEvm())->toBeTrue();
        expect(PaymentNetwork::ETHEREUM->isEvm())->toBeTrue();
        expect(PaymentNetwork::SOLANA->isEvm())->toBeFalse();
        expect(PaymentNetwork::TRON->isEvm())->toBeFalse();
    });

    it('validates EVM address pattern', function (): void {
        $pattern = PaymentNetwork::POLYGON->addressPattern();
        expect((bool) preg_match($pattern, '0x742d35Cc6634C0532925a3b844Bc9e7595f44e12'))->toBeTrue();
        expect((bool) preg_match($pattern, 'invalid'))->toBeFalse();
        expect((bool) preg_match($pattern, '0xshort'))->toBeFalse();
    });

    it('returns avg confirmation seconds for all networks', function (): void {
        expect(PaymentNetwork::SOLANA->avgConfirmationSeconds())->toBe(5);
        expect(PaymentNetwork::TRON->avgConfirmationSeconds())->toBe(30);
        expect(PaymentNetwork::POLYGON->avgConfirmationSeconds())->toBe(6);
        expect(PaymentNetwork::BASE->avgConfirmationSeconds())->toBe(2);
        expect(PaymentNetwork::ARBITRUM->avgConfirmationSeconds())->toBe(3);
        expect(PaymentNetwork::ETHEREUM->avgConfirmationSeconds())->toBe(15);
    });
});
