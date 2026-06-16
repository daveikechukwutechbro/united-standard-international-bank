<?php

declare(strict_types=1);

namespace App\Domain\Interledger\Services;

use InvalidArgumentException;

/**
 * Cross-currency quote service for ILP.
 *
 * Note: Uses float arithmetic for rate calculations in simulation mode.
 * Production deployment should replace with bcmath or fixed-point library
 * for amounts exceeding 15 significant digits.
 */
class QuoteService
{
    /**
     * Approximate exchange rates relative to USD (simplified for demo/simulation).
     *
     * @var array<string, float>
     */
    private const BASE_RATES = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'BTC' => 0.000015,
        'ETH' => 0.00035,
    ];

    /**
     * Fixed fee per cross-currency conversion (in send-asset units, percentage).
     */
    private const FEE_RATE = 0.001; // 0.1 %

    /**
     * Quote TTL in seconds.
     */
    private const QUOTE_TTL = 30;

    /**
     * Get a cross-currency quote.
     *
     * @return array{
     *   send_amount: string,
     *   send_asset: string,
     *   receive_amount: string,
     *   receive_asset: string,
     *   exchange_rate: float,
     *   fee: string,
     *   expires_at: string,
     * }
     */
    public function getQuote(string $sendAsset, string $receiveAsset, string $sendAmount): array
    {
        $rate = $this->getExchangeRate($sendAsset, $receiveAsset);
        $sendFloat = (float) $sendAmount;
        $fee = $sendFloat * self::FEE_RATE;
        $netSend = $sendFloat - $fee;
        $receive = $netSend * $rate;
        $expiresAt = now()->addSeconds(self::QUOTE_TTL)->toIso8601String();

        return [
            'send_amount'    => $sendAmount,
            'send_asset'     => $sendAsset,
            'receive_amount' => number_format($receive, $this->assetScale($receiveAsset), '.', ''),
            'receive_asset'  => $receiveAsset,
            'exchange_rate'  => $rate,
            'fee'            => number_format($fee, $this->assetScale($sendAsset), '.', ''),
            'expires_at'     => $expiresAt,
        ];
    }

    /**
     * Return the list of supported assets from configuration.
     *
     * @return array<string, array{code: string, scale: int}>
     */
    public function getSupportedAssets(): array
    {
        /** @var array<string, array{code: string, scale: int}> $assets */
        $assets = config('interledger.supported_assets', []);

        return $assets;
    }

    /**
     * Get the exchange rate from one asset to another.
     *
     * @throws InvalidArgumentException when an asset is not supported.
     */
    public function getExchangeRate(string $fromAsset, string $toAsset): float
    {
        $fromUpper = strtoupper($fromAsset);
        $toUpper = strtoupper($toAsset);

        if (! array_key_exists($fromUpper, self::BASE_RATES)) {
            throw new InvalidArgumentException("Unsupported asset: {$fromAsset}");
        }

        if (! array_key_exists($toUpper, self::BASE_RATES)) {
            throw new InvalidArgumentException("Unsupported asset: {$toAsset}");
        }

        // Convert via USD as the common denominator.
        // All entries in BASE_RATES are non-zero, so division is always safe.
        $fromRate = self::BASE_RATES[$fromUpper];
        $toRate = self::BASE_RATES[$toUpper];

        return $toRate / $fromRate;
    }

    /**
     * Return the decimal scale for a given asset code.
     */
    private function assetScale(string $assetCode): int
    {
        /** @var array<string, array{code: string, scale: int}> $assets */
        $assets = config('interledger.supported_assets', []);

        return $assets[strtoupper($assetCode)]['scale'] ?? 2;
    }
}
