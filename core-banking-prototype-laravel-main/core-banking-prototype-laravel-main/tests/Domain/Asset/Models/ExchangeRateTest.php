<?php

declare(strict_types=1);

use App\Domain\Asset\Models\ExchangeRate;

it('can create exchange rate', function () {
    // Assets are already seeded in migrations

    $rate = ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->valid()
        ->create([
            'valid_at'   => now()->subMinutes(10),
            'expires_at' => now()->addHours(1),
        ]);

    expect($rate->from_asset_code)->toBe('USD');
    expect($rate->to_asset_code)->toBe('EUR');
    expect($rate->rate)->toBeString(); // Decimal cast returns string
    expect($rate->isValid())->toBeTrue();
});

it('can convert amounts using exchange rate', function () {
    $rate = ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->create(['rate' => 0.85]);

    $usdAmount = 10000; // $100.00 in cents
    $eurAmount = $rate->convert($usdAmount);

    expect($eurAmount)->toBe(8500); // €85.00 in cents
});

it('can calculate inverse rate', function () {
    $rate = ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->create(['rate' => 0.85]);

    $inverseRate = $rate->getInverseRate();

    expect(round($inverseRate, 3))->toBe(1.176);
});

it('can check if rate is expired', function () {
    $expiredRate = ExchangeRate::factory()
        ->expired()
        ->create();

    $validRate = ExchangeRate::factory()
        ->valid()
        ->create();

    expect($expiredRate->isExpired())->toBeTrue();
    expect($expiredRate->isValid())->toBeFalse();
    expect($validRate->isExpired())->toBeFalse();
    expect($validRate->isValid())->toBeTrue();
});

it('can scope to valid rates', function () {
    ExchangeRate::factory()->expired()->count(2)->create();
    ExchangeRate::factory()->valid()->count(3)->create();

    $validRates = ExchangeRate::valid()->get();

    expect($validRates)->toHaveCount(3);
    foreach ($validRates as $rate) {
        expect($rate->isValid())->toBeTrue();
    }
});

it('can scope between specific assets', function () {
    ExchangeRate::factory()->between('USD', 'EUR')->create();
    ExchangeRate::factory()->between('USD', 'GBP')->create();
    ExchangeRate::factory()->between('EUR', 'GBP')->create();

    $usdEurRates = ExchangeRate::between('USD', 'EUR')->get();

    expect($usdEurRates)->toHaveCount(1);
    expect($usdEurRates->first()->from_asset_code)->toBe('USD');
    expect($usdEurRates->first()->to_asset_code)->toBe('EUR');
});

it('can get age in minutes', function () {
    $rate = ExchangeRate::factory()
        ->create(['valid_at' => now()->subMinutes(30)]);

    expect($rate->getAgeInMinutes())->toBeGreaterThanOrEqual(29);
    expect($rate->getAgeInMinutes())->toBeLessThanOrEqual(31);
});
