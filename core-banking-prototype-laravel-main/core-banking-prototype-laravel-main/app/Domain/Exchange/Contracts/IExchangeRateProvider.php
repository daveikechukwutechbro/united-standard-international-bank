<?php

declare(strict_types=1);

namespace App\Domain\Exchange\Contracts;

use App\Domain\Exchange\ValueObjects\ExchangeRateQuote;
use App\Domain\Exchange\ValueObjects\RateProviderCapabilities;

interface IExchangeRateProvider
{
    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Check if provider is available.
     */
    public function isAvailable(): bool;

    /**
     * Get exchange rate for a currency pair.
     *
     * @throws \App\Domain\Exchange\Exceptions\RateProviderException
     */
    public function getRate(string $fromCurrency, string $toCurrency): ExchangeRateQuote;

    /**
     * Get multiple exchange rates at once.
     *
     * @param  array<string> $pairs Array of currency pairs like ['USD/EUR', 'BTC/USD']
     * @return array<string, ExchangeRateQuote>
     */
    public function getRates(array $pairs): array;

    /**
     * Get all available rates for a base currency.
     *
     * @return array<string, ExchangeRateQuote>
     */
    public function getAllRatesForBase(string $baseCurrency): array;

    /**
     * Get provider capabilities.
     */
    public function getCapabilities(): RateProviderCapabilities;

    /**
     * Get supported currency codes.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array;

    /**
     * Check if a currency pair is supported.
     */
    public function supportsPair(string $fromCurrency, string $toCurrency): bool;

    /**
     * Get provider priority (higher = preferred).
     */
    public function getPriority(): int;
}
