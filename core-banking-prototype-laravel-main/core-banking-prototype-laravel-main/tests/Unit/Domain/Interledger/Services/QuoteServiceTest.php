<?php

declare(strict_types=1);

use App\Domain\Interledger\Services\QuoteService;
use Tests\TestCase;

uses(TestCase::class);

describe('QuoteService', function (): void {
    beforeEach(function (): void {
        $this->service = new QuoteService();
    });

    describe('getSupportedAssets()', function (): void {
        it('returns the assets defined in configuration', function (): void {
            $assets = $this->service->getSupportedAssets();

            expect($assets)->toBeArray();
            expect($assets)->toHaveKey('USD');
            expect($assets)->toHaveKey('EUR');
            expect($assets)->toHaveKey('GBP');
            expect($assets)->toHaveKey('BTC');
            expect($assets)->toHaveKey('ETH');
        });

        it('returns assets with code and scale keys', function (): void {
            $assets = $this->service->getSupportedAssets();

            foreach ($assets as $asset) {
                expect($asset)->toHaveKey('code');
                expect($asset)->toHaveKey('scale');
                expect($asset['scale'])->toBeInt();
            }
        });

        it('returns USD with scale 2', function (): void {
            $assets = $this->service->getSupportedAssets();

            expect($assets['USD']['scale'])->toBe(2);
        });

        it('returns BTC with scale 8', function (): void {
            $assets = $this->service->getSupportedAssets();

            expect($assets['BTC']['scale'])->toBe(8);
        });

        it('returns ETH with scale 18', function (): void {
            $assets = $this->service->getSupportedAssets();

            expect($assets['ETH']['scale'])->toBe(18);
        });
    });

    describe('getExchangeRate()', function (): void {
        it('returns a float for a valid asset pair', function (): void {
            $rate = $this->service->getExchangeRate('USD', 'EUR');

            expect($rate)->toBeFloat();
            expect($rate)->toBeGreaterThan(0.0);
        });

        it('returns 1.0 for the same asset', function (): void {
            $rate = $this->service->getExchangeRate('USD', 'USD');

            expect($rate)->toBe(1.0);
        });

        it('returns a sensible EUR/USD rate', function (): void {
            $rate = $this->service->getExchangeRate('USD', 'EUR');

            // EUR is roughly 0.85–0.95 per USD
            expect($rate)->toBeGreaterThan(0.5);
            expect($rate)->toBeLessThan(1.5);
        });

        it('throws InvalidArgumentException for unsupported send asset', function (): void {
            expect(fn () => $this->service->getExchangeRate('XYZ', 'USD'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws InvalidArgumentException for unsupported receive asset', function (): void {
            expect(fn () => $this->service->getExchangeRate('USD', 'XYZ'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('getQuote()', function (): void {
        it('returns a quote array with all required keys', function (): void {
            $quote = $this->service->getQuote('USD', 'EUR', '100.00');

            expect($quote)->toHaveKeys([
                'send_amount',
                'send_asset',
                'receive_amount',
                'receive_asset',
                'exchange_rate',
                'fee',
                'expires_at',
            ]);
        });

        it('reflects the input send amount and assets', function (): void {
            $quote = $this->service->getQuote('USD', 'EUR', '50.00');

            expect($quote['send_amount'])->toBe('50.00');
            expect($quote['send_asset'])->toBe('USD');
            expect($quote['receive_asset'])->toBe('EUR');
        });

        it('includes a non-zero fee', function (): void {
            $quote = $this->service->getQuote('USD', 'EUR', '1000.00');

            expect((float) $quote['fee'])->toBeGreaterThan(0.0);
        });

        it('sets an expires_at in the future', function (): void {
            $quote = $this->service->getQuote('USD', 'EUR', '100.00');

            $expiresAt = Carbon\Carbon::parse($quote['expires_at']);

            expect($expiresAt->isFuture())->toBeTrue();
        });
    });
});
