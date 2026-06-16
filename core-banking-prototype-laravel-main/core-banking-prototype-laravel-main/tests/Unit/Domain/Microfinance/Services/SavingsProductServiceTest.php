<?php

declare(strict_types=1);

use App\Domain\Microfinance\Services\SavingsProductService;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('SavingsProductService has checkDormancy method', function (): void {
    $service = new SavingsProductService();
    expect((new ReflectionClass($service))->hasMethod('checkDormancy'))->toBeTrue();
});

it('SavingsProductService has applyDormancyStatus method', function (): void {
    $service = new SavingsProductService();
    expect((new ReflectionClass($service))->hasMethod('applyDormancyStatus'))->toBeTrue();
});

it('SavingsProductService has calculateInterest method', function (): void {
    $service = new SavingsProductService();
    expect((new ReflectionClass($service))->hasMethod('calculateInterest'))->toBeTrue();
});

it('SavingsProductService has compoundInterest method', function (): void {
    $service = new SavingsProductService();
    expect((new ReflectionClass($service))->hasMethod('compoundInterest'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral: checkDormancy
// ---------------------------------------------------------------------------

it('returns not dormant when no activity data supplied', function (): void {
    $service = new SavingsProductService();
    $user = App\Models\User::factory()->create();

    // Create a share account to look up
    $account = App\Domain\Microfinance\Models\ShareAccount::create([
        'user_id'          => $user->id,
        'account_number'   => 'SHA-TESTDORM',
        'shares_purchased' => 0,
        'nominal_value'    => '100.00',
        'total_value'      => '0.00',
        'status'           => App\Domain\Microfinance\Enums\ShareAccountStatus::ACTIVE,
        'currency'         => 'USD',
        'dividend_balance' => '0.00',
    ]);

    $result = $service->checkDormancy($account->id);

    expect($result['is_dormant'])->toBeFalse();
    expect($result['days_inactive'])->toBe(0);
    expect($result)->toHaveKeys(['is_dormant', 'days_inactive', 'threshold']);
});

it('returns dormant when days inactive exceeds threshold', function (): void {
    $service = new SavingsProductService();
    $user = App\Models\User::factory()->create();
    $account = App\Domain\Microfinance\Models\ShareAccount::create([
        'user_id'          => $user->id,
        'account_number'   => 'SHA-TESTDORM2',
        'shares_purchased' => 0,
        'nominal_value'    => '100.00',
        'total_value'      => '0.00',
        'status'           => App\Domain\Microfinance\Enums\ShareAccountStatus::ACTIVE,
        'currency'         => 'USD',
        'dividend_balance' => '0.00',
    ]);
    $threshold = (int) config('microfinance.dormancy.days_until_dormant', 180);

    $result = $service->checkDormancy($account->id, $threshold + 1);

    expect($result['is_dormant'])->toBeTrue();
    expect($result['days_inactive'])->toBe($threshold + 1);
});

// ---------------------------------------------------------------------------
// Behavioral: calculateInterest (simple interest)
// ---------------------------------------------------------------------------

it('calculates simple interest correctly', function (): void {
    $service = new SavingsProductService();
    // $1000 at 12% for 30 days = $1000 * 0.12 * 30/365 = $9.86...
    $interest = $service->calculateInterest('1000.00', 0.12, 30);
    expect((float) $interest)->toBeGreaterThan(9.85);
    expect((float) $interest)->toBeLessThan(9.87);
});

it('returns zero interest for zero days', function (): void {
    $service = new SavingsProductService();
    $interest = $service->calculateInterest('1000.00', 0.12, 0);
    expect((float) $interest)->toBe(0.00);
});

it('calculates interest proportionally for different balances', function (): void {
    $service = new SavingsProductService();
    $interest1 = $service->calculateInterest('1000.00', 0.10, 365);
    $interest2 = $service->calculateInterest('2000.00', 0.10, 365);
    expect((float) $interest2)->toBeGreaterThan((float) $interest1 * 1.9);
    expect((float) $interest2)->toBeLessThan((float) $interest1 * 2.1);
});

it('calculates annual interest at 10% correctly', function (): void {
    $service = new SavingsProductService();
    // $1000 at 10% for 365 days = $100.00
    $interest = $service->calculateInterest('1000.00', 0.10, 365);
    expect((float) $interest)->toBeGreaterThan(99.99);
    expect((float) $interest)->toBeLessThan(100.01);
});

// ---------------------------------------------------------------------------
// Behavioral: compoundInterest
// ---------------------------------------------------------------------------

it('calculates compound interest as higher than simple interest', function (): void {
    $service = new SavingsProductService();
    $simple = (float) $service->calculateInterest('1000.00', 0.12, 365);
    $compound = (float) $service->compoundInterest('1000.00', 0.12, 365, 'monthly');
    // Compound should exceed simple for multi-period
    expect($compound)->toBeGreaterThan($simple);
});

it('calculates compound interest for 1 year at 12% monthly', function (): void {
    $service = new SavingsProductService();
    // A = 1000 * (1 + 0.12/12)^12 = 1000 * 1.126825... → interest ≈ 126.83
    $compound = (float) $service->compoundInterest('1000.00', 0.12, 365, 'monthly');
    expect($compound)->toBeGreaterThan(126.00);
    expect($compound)->toBeLessThan(128.00);
});

it('returns zero compound interest for zero days', function (): void {
    $service = new SavingsProductService();
    $compound = (float) $service->compoundInterest('1000.00', 0.12, 0, 'monthly');
    expect($compound)->toBe(0.00);
});

it('throws for unknown compounding frequency', function (): void {
    $service = new SavingsProductService();

    expect(fn () => $service->compoundInterest('1000.00', 0.12, 365, 'hourly'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws for negative days in calculateInterest', function (): void {
    $service = new SavingsProductService();

    expect(fn () => $service->calculateInterest('1000.00', 0.12, -1))
        ->toThrow(InvalidArgumentException::class);
});

it('applyDormancyStatus returns an integer', function (): void {
    $service = new SavingsProductService();
    $result = $service->applyDormancyStatus();

    expect($result)->toBeInt();
});
