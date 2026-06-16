<?php

declare(strict_types=1);

use App\Domain\Microfinance\Models\TellerCashier;
use App\Domain\Microfinance\Services\TellerService;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('TellerService has registerTeller method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('registerTeller'))->toBeTrue();
});

it('TellerService has recordCashIn method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('recordCashIn'))->toBeTrue();
});

it('TellerService has recordCashOut method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('recordCashOut'))->toBeTrue();
});

it('TellerService has reconcile method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('reconcile'))->toBeTrue();
});

it('TellerService has getVaultBalance method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('getVaultBalance'))->toBeTrue();
});

it('TellerService has deactivateTeller method', function (): void {
    $service = new TellerService();
    expect((new ReflectionClass($service))->hasMethod('deactivateTeller'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral
// ---------------------------------------------------------------------------

it('registers a teller with zero vault balance', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'Jane Doe', 'Branch A');

    expect($teller)->toBeInstanceOf(TellerCashier::class);
    expect($teller->name)->toBe('Jane Doe');
    expect($teller->branch)->toBe('Branch A');
    expect((float) $teller->vault_balance)->toBe(0.0);
    expect($teller->is_active)->toBeTrue();
});

it('records cash in and increases vault balance', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'John Doe');

    $updated = $service->recordCashIn($teller->id, '500.00');

    expect((float) $updated->vault_balance)->toBe(500.00);
});

it('records cash out and decreases vault balance', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'John Doe');

    $service->recordCashIn($teller->id, '1000.00');
    $updated = $service->recordCashOut($teller->id, '300.00');

    expect((float) $updated->vault_balance)->toBe(700.00);
});

it('throws when cash out exceeds vault balance', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'John Doe');

    expect(fn () => $service->recordCashOut($teller->id, '100.00'))
        ->toThrow(RuntimeException::class);
});

it('reconciles a teller and sets last_reconciled_at', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'John Doe');

    expect($teller->last_reconciled_at)->toBeNull();

    $reconciled = $service->reconcile($teller->id);

    expect($reconciled->last_reconciled_at)->not->toBeNull();
});

it('returns vault balance array with correct keys', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'John Doe');
    $service->recordCashIn($teller->id, '250.00');

    $balance = $service->getVaultBalance($teller->id);

    expect($balance)->toHaveKeys(['balance', 'currency', 'last_reconciled']);
    expect($balance['balance'])->toBe('250.00');
    expect($balance['currency'])->toBe('USD');
    expect($balance['last_reconciled'])->toBeNull();
});

it('deactivates a teller', function (): void {
    $service = new TellerService();
    $user = App\Models\User::factory()->create();
    $teller = $service->registerTeller($user->id, 'Jane Doe');

    $deactivated = $service->deactivateTeller($teller->id);

    expect($deactivated->is_active)->toBeFalse();
});

it('throws when operating on non-existent teller', function (): void {
    $service = new TellerService();

    expect(fn () => $service->recordCashIn('non-existent-uuid', '100.00'))
        ->toThrow(RuntimeException::class);
});
