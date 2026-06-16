<?php

declare(strict_types=1);

namespace App\Domain\Exchange\ValueObjects;

use Carbon\Carbon;

final class ExchangeRateQuote
{
    public function __construct(
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $rate,
        public readonly float $bid,
        public readonly float $ask,
        public readonly string $provider,
        public readonly Carbon $timestamp,
        public readonly ?float $volume24h = null,
        public readonly ?float $change24h = null,
        public readonly ?array $metadata = []
    ) {
    }

    /**
     * Get the mid-market rate.
     */
    public function getMidRate(): float
    {
        return $this->rate;
    }

    /**
     * Get the spread between bid and ask.
     */
    public function getSpread(): float
    {
        return $this->ask - $this->bid;
    }

    /**
     * Get spread as percentage.
     */
    public function getSpreadPercentage(): float
    {
        if ($this->rate == 0) {
            return 0;
        }

        return ($this->getSpread() / $this->rate) * 100;
    }

    /**
     * Check if quote is fresh (within given seconds).
     */
    public function isFresh(int $maxAgeSeconds = 300): bool
    {
        return $this->timestamp->diffInSeconds(now()) <= $maxAgeSeconds;
    }

    /**
     * Get age in seconds.
     */
    public function getAgeInSeconds(): int
    {
        return (int) $this->timestamp->diffInSeconds(now());
    }

    /**
     * Convert an amount using this quote.
     */
    public function convert(float $amount, bool $useBidAsk = false, string $direction = 'buy'): float
    {
        if ($useBidAsk) {
            $rate = $direction === 'buy' ? $this->ask : $this->bid;
        } else {
            $rate = $this->rate;
        }

        return $amount * $rate;
    }

    /**
     * Create inverse quote.
     */
    public function inverse(): self
    {
        return new self(
            fromCurrency: $this->toCurrency,
            toCurrency: $this->fromCurrency,
            rate: 1 / $this->rate,
            bid: 1 / $this->ask, // Inverted: bid becomes 1/ask
            ask: 1 / $this->bid, // Inverted: ask becomes 1/bid
            provider: $this->provider,
            timestamp: $this->timestamp,
            volume24h: $this->volume24h,
            change24h: $this->change24h ? -$this->change24h : null,
            metadata: $this->metadata
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'from_currency'     => $this->fromCurrency,
            'to_currency'       => $this->toCurrency,
            'rate'              => $this->rate,
            'bid'               => $this->bid,
            'ask'               => $this->ask,
            'spread'            => $this->getSpread(),
            'spread_percentage' => $this->getSpreadPercentage(),
            'provider'          => $this->provider,
            'timestamp'         => $this->timestamp->toISOString(),
            'age_seconds'       => $this->getAgeInSeconds(),
            'volume_24h'        => $this->volume24h,
            'change_24h'        => $this->change24h,
            'metadata'          => $this->metadata,
        ];
    }
}
