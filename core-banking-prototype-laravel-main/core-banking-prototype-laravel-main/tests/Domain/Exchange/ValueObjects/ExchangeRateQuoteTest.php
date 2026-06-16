<?php

declare(strict_types=1);

use App\Domain\Exchange\ValueObjects\ExchangeRateQuote;
use Carbon\Carbon;

it('can create exchange rate quote', function () {
    $timestamp = Carbon::now();

    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test-provider',
        timestamp: $timestamp,
        volume24h: 1000000.0,
        change24h: 0.02,
        metadata: ['source' => 'test']
    );

    expect($quote->fromCurrency)->toBe('USD');
    expect($quote->toCurrency)->toBe('EUR');
    expect($quote->rate)->toBe(0.85);
    expect($quote->bid)->toBe(0.849);
    expect($quote->ask)->toBe(0.851);
    expect($quote->provider)->toBe('test-provider');
    expect($quote->timestamp)->toBe($timestamp);
    expect($quote->volume24h)->toBe(1000000.0);
    expect($quote->change24h)->toBe(0.02);
    expect($quote->metadata)->toBe(['source' => 'test']);
});

it('can calculate spread', function () {
    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 1.0,
        bid: 0.995,
        ask: 1.005,
        provider: 'test',
        timestamp: Carbon::now()
    );

    expect($quote->getSpread())->toEqualWithDelta(0.01, 0.0001);
    expect($quote->getSpreadPercentage())->toEqualWithDelta(1.0, 0.01);
});

it('can check freshness', function () {
    $freshQuote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test',
        timestamp: Carbon::now()
    );

    $staleQuote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test',
        timestamp: Carbon::now()->subMinutes(10)
    );

    expect($freshQuote->isFresh(300))->toBeTrue();
    expect($staleQuote->isFresh(300))->toBeFalse();
    expect($freshQuote->getAgeInSeconds())->toBeLessThan(5);
    expect($staleQuote->getAgeInSeconds())->toBeGreaterThan(590);
});

it('can convert amounts', function () {
    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test',
        timestamp: Carbon::now()
    );

    // Using mid rate
    expect($quote->convert(100))->toBe(85.0);

    // Using bid/ask
    expect($quote->convert(100, true, 'buy'))->toEqualWithDelta(85.1, 0.01);
    expect($quote->convert(100, true, 'sell'))->toEqualWithDelta(84.9, 0.01);
});

it('can create inverse quote', function () {
    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test',
        timestamp: Carbon::now(),
        volume24h: 1000000.0,
        change24h: 0.02
    );

    $inverse = $quote->inverse();

    expect($inverse->fromCurrency)->toBe('EUR');
    expect($inverse->toCurrency)->toBe('USD');
    expect($inverse->rate)->toBeGreaterThan(1.176);
    expect($inverse->rate)->toBeLessThan(1.177);
    expect($inverse->bid)->toBeLessThan($inverse->rate);
    expect($inverse->ask)->toBeGreaterThan($inverse->rate);
    expect($inverse->change24h)->toBe(-0.02);
});

it('converts to array correctly', function () {
    $timestamp = Carbon::now();

    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0.85,
        bid: 0.849,
        ask: 0.851,
        provider: 'test-provider',
        timestamp: $timestamp,
        volume24h: 1000000.0,
        change24h: 0.02,
        metadata: ['source' => 'test']
    );

    $array = $quote->toArray();

    expect($array)->toHaveKeys([
        'from_currency',
        'to_currency',
        'rate',
        'bid',
        'ask',
        'spread',
        'spread_percentage',
        'provider',
        'timestamp',
        'age_seconds',
        'volume_24h',
        'change_24h',
        'metadata',
    ]);

    expect($array['from_currency'])->toBe('USD');
    expect($array['to_currency'])->toBe('EUR');
    expect($array['rate'])->toBe(0.85);
    expect($array['spread'])->toEqualWithDelta(0.002, 0.0001);
    expect($array['spread_percentage'])->toBeGreaterThan(0.23);
    expect($array['spread_percentage'])->toBeLessThan(0.24);
});

it('handles zero rate for spread percentage', function () {
    $quote = new ExchangeRateQuote(
        fromCurrency: 'USD',
        toCurrency: 'EUR',
        rate: 0,
        bid: 0,
        ask: 0,
        provider: 'test',
        timestamp: Carbon::now()
    );

    expect($quote->getSpreadPercentage())->toBe(0.0);
});
