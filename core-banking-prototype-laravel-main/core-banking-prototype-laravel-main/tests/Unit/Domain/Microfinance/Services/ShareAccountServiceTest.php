<?php

declare(strict_types=1);

use App\Domain\Microfinance\Enums\ShareAccountStatus;
use App\Domain\Microfinance\Models\ShareAccount;
use App\Domain\Microfinance\Services\ShareAccountService;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('ShareAccountService has openAccount method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('openAccount'))->toBeTrue();
});

it('ShareAccountService has purchaseShares method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('purchaseShares'))->toBeTrue();
});

it('ShareAccountService has redeemShares method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('redeemShares'))->toBeTrue();
});

it('ShareAccountService has calculateDividend method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('calculateDividend'))->toBeTrue();
});

it('ShareAccountService has distributeDividend method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('distributeDividend'))->toBeTrue();
});

it('ShareAccountService has closeAccount method', function (): void {
    $service = new ShareAccountService();
    expect((new ReflectionClass($service))->hasMethod('closeAccount'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral
// ---------------------------------------------------------------------------

it('opens an account with correct initial values', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id, null, 'USD');

    expect($account)->toBeInstanceOf(ShareAccount::class);
    expect($account->status)->toBe(ShareAccountStatus::ACTIVE);
    expect($account->shares_purchased)->toBe(0);
    expect((float) $account->total_value)->toBe(0.0);
    expect($account->currency)->toBe('USD');
    expect($account->account_number)->toStartWith('SHA-');
});

it('generates unique account numbers', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account1 = $service->openAccount($user->id);
    $account2 = $service->openAccount($user->id);

    expect($account1->account_number)->not->toBe($account2->account_number);
});

it('purchases shares and updates total_value', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);

    $updated = $service->purchaseShares($account->id, 5);

    expect($updated->shares_purchased)->toBe(5);
    // 5 shares * nominal_value (100.00) = 500.00
    expect((float) $updated->total_value)->toBe(500.00);
});

it('redeems shares and reduces total_value', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);
    $service->purchaseShares($account->id, 10);

    $updated = $service->redeemShares($account->id, 3);

    expect($updated->shares_purchased)->toBe(7);
});

it('throws when purchasing shares beyond max limit', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);

    $maxShares = (int) config('microfinance.share_accounts.max_shares', 1000);

    expect(fn () => $service->purchaseShares($account->id, $maxShares + 1))
        ->toThrow(RuntimeException::class);
});

it('throws when redeeming more shares than available', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);
    $service->purchaseShares($account->id, 5);

    expect(fn () => $service->redeemShares($account->id, 10))
        ->toThrow(RuntimeException::class);
});

it('calculates dividend correctly', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);
    $service->purchaseShares($account->id, 10);

    $dividend = $service->calculateDividend($account->id, 2.50);

    expect($dividend['shares'])->toBe(10);
    expect($dividend['dividend_per_share'])->toBe(2.50);
    expect($dividend['total_dividend'])->toBe(25.00);
});

it('distributes dividend and adds to dividend_balance', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);
    $service->purchaseShares($account->id, 10);

    $updated = $service->distributeDividend($account->id, 2.50);

    expect((float) $updated->dividend_balance)->toBe(25.00);
});

it('closes an account and sets status to CLOSED', function (): void {
    $service = new ShareAccountService();
    $user = App\Models\User::factory()->create();
    $account = $service->openAccount($user->id);

    $closed = $service->closeAccount($account->id);

    expect($closed->status)->toBe(ShareAccountStatus::CLOSED);
});
