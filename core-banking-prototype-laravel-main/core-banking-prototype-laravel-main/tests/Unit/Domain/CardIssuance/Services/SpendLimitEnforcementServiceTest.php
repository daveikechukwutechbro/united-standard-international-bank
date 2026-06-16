<?php

declare(strict_types=1);

use App\Domain\CardIssuance\Models\Card;
use App\Domain\CardIssuance\Models\Cardholder;
use App\Domain\CardIssuance\Services\SpendLimitEnforcementService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    Cache::flush();

    $this->service = new SpendLimitEnforcementService();

    $this->testUser = User::factory()->create();
    $this->testCardholder = Cardholder::create([
        'user_id'    => $this->testUser->id,
        'first_name' => 'Test',
        'last_name'  => 'User',
        'kyc_status' => 'verified',
    ]);
});

it('allows spend within daily limit', function (): void {
    DB::table('cards')->insert([
        'id'                   => (string) Illuminate\Support\Str::uuid(),
        'user_id'              => $this->testUser->id,
        'cardholder_id'        => $this->testCardholder->id,
        'issuer_card_token'    => 'token_daily_ok',
        'issuer'               => 'rain',
        'last4'                => '1111',
        'network'              => 'visa',
        'status'               => 'active',
        'currency'             => 'USD',
        'spend_limit_cents'    => 10000, // $100
        'spend_limit_interval' => 'daily',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $result = $this->service->checkLimit('token_daily_ok', 50.00);

    expect($result)->toBeTrue();
});

it('rejects spend exceeding daily limit', function (): void {
    DB::table('cards')->insert([
        'id'                   => (string) Illuminate\Support\Str::uuid(),
        'user_id'              => $this->testUser->id,
        'cardholder_id'        => $this->testCardholder->id,
        'issuer_card_token'    => 'token_daily_exceed',
        'issuer'               => 'rain',
        'last4'                => '2222',
        'network'              => 'visa',
        'status'               => 'active',
        'currency'             => 'USD',
        'spend_limit_cents'    => 10000, // $100
        'spend_limit_interval' => 'daily',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    // Record $80 of prior spend
    $this->service->recordSpend('token_daily_exceed', 80.00);

    // Try to spend $30 more — should exceed $100 limit
    $result = $this->service->checkLimit('token_daily_exceed', 30.00);

    expect($result)->toBeFalse();
});

it('allows spend when no limit configured', function (): void {
    // No card exists for this token — should return true (no limit)
    $result = $this->service->checkLimit('token_nonexistent', 999.99);

    expect($result)->toBeTrue();
});

it('tracks monthly spend separately from daily', function (): void {
    // Create a card with monthly limit
    DB::table('cards')->insert([
        'id'                   => (string) Illuminate\Support\Str::uuid(),
        'user_id'              => $this->testUser->id,
        'cardholder_id'        => $this->testCardholder->id,
        'issuer_card_token'    => 'token_monthly',
        'issuer'               => 'rain',
        'last4'                => '3333',
        'network'              => 'visa',
        'status'               => 'active',
        'currency'             => 'USD',
        'spend_limit_cents'    => 50000, // $500 monthly
        'spend_limit_interval' => 'monthly',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    // Record $400 monthly spend
    $this->service->recordSpend('token_monthly', 400.00);

    // $90 more should be allowed (total $490 < $500)
    expect($this->service->checkLimit('token_monthly', 90.00))->toBeTrue();

    // $110 more should be rejected (total $510 > $500)
    expect($this->service->checkLimit('token_monthly', 110.00))->toBeFalse();

    // Verify the cache key uses monthly format (Y-m), not daily (Y-m-d)
    $monthlyKey = 'card_spend:monthly:token_monthly:' . date('Y-m');
    $dailyKey = 'card_spend:daily:token_monthly:' . date('Y-m-d');

    expect(Cache::get($monthlyKey))->toBe(40000); // 400.00 * 100
    expect(Cache::get($dailyKey))->toBeNull();
});

it('allows spend when card has null spend limit', function (): void {
    DB::table('cards')->insert([
        'id'                   => (string) Illuminate\Support\Str::uuid(),
        'user_id'              => $this->testUser->id,
        'cardholder_id'        => $this->testCardholder->id,
        'issuer_card_token'    => 'token_no_limit',
        'issuer'               => 'rain',
        'last4'                => '4444',
        'network'              => 'visa',
        'status'               => 'active',
        'currency'             => 'USD',
        'spend_limit_cents'    => null,
        'spend_limit_interval' => null,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $result = $this->service->checkLimit('token_no_limit', 999.99);

    expect($result)->toBeTrue();
});

it('records spend and accumulates correctly', function (): void {
    DB::table('cards')->insert([
        'id'                   => (string) Illuminate\Support\Str::uuid(),
        'user_id'              => $this->testUser->id,
        'cardholder_id'        => $this->testCardholder->id,
        'issuer_card_token'    => 'token_accumulate',
        'issuer'               => 'rain',
        'last4'                => '5555',
        'network'              => 'visa',
        'status'               => 'active',
        'currency'             => 'USD',
        'spend_limit_cents'    => 20000, // $200
        'spend_limit_interval' => 'daily',
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    // Record multiple spends
    $this->service->recordSpend('token_accumulate', 50.00);
    $this->service->recordSpend('token_accumulate', 75.00);

    // Total is $125, limit is $200 — $74 more should work
    expect($this->service->checkLimit('token_accumulate', 74.00))->toBeTrue();

    // Total is $125, limit is $200 — $76 more should fail
    expect($this->service->checkLimit('token_accumulate', 76.00))->toBeFalse();
});
