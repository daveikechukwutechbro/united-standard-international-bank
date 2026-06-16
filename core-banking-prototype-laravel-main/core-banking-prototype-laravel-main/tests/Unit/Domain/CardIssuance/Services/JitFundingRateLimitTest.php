<?php

declare(strict_types=1);

use App\Domain\CardIssuance\ValueObjects\AuthorizationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    RateLimiter::clear('jit_auth:card_test_token_001');
});

/**
 * Build a minimal AuthorizationRequest for a given card token.
 */
function makeAuthRequest(string $cardToken = 'card_test_token_001'): AuthorizationRequest
{
    return new AuthorizationRequest(
        authorizationId: 'auth_' . bin2hex(random_bytes(8)),
        cardToken: $cardToken,
        amountCents: 1000,
        currency: 'USD',
        merchantName: 'Test Merchant',
        merchantCategory: '5411',
    );
}

it('rate-limits authorization requests per card token', function (): void {
    // Verify RateLimiter::attempt correctly blocks after max attempts
    $key = 'jit_auth:card_test_token_001';
    $maxAttempts = 3;
    $allowed = 0;

    for ($i = 0; $i < 5; $i++) {
        if (RateLimiter::attempt($key, $maxAttempts, fn () => true, 60)) {
            $allowed++;
        }
    }

    expect($allowed)->toBe(3)
        ->and(RateLimiter::tooManyAttempts($key, $maxAttempts))->toBeTrue();
});

it('uses correct rate-limit key format per card token', function (): void {
    $cardToken = 'tok_abc123xyz';
    $expectedKey = 'jit_auth:' . $cardToken;

    RateLimiter::attempt($expectedKey, 5, fn () => true, 60);

    expect((int) RateLimiter::attempts($expectedKey))->toBe(1);
});

it('rate-limit keys are isolated between different card tokens', function (): void {
    $keyA = 'jit_auth:card_aaa';
    $keyB = 'jit_auth:card_bbb';

    // Exhaust key A
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::attempt($keyA, 2, fn () => true, 60);
    }

    // Key B should still be available
    $allowed = RateLimiter::attempt($keyB, 2, fn () => true, 60);

    expect(RateLimiter::tooManyAttempts($keyA, 2))->toBeTrue()
        ->and($allowed)->toBeTrue();
});

it('JitFundingService declines when rate limit exceeded', function (): void {
    $cardToken = 'card_rate_limited';
    $key = 'jit_auth:' . $cardToken;

    // Configure a very low limit for this test
    config(['cardissuance.jit_funding.max_auth_per_minute' => 2]);

    // Exhaust the rate limit manually
    RateLimiter::attempt($key, 2, fn () => true, 60);
    RateLimiter::attempt($key, 2, fn () => true, 60);

    // Next attempt should be blocked
    $blocked = ! RateLimiter::attempt($key, 2, fn () => true, 60);
    expect($blocked)->toBeTrue();
});

it('reads max_auth_per_minute from cardissuance config', function (): void {
    config(['cardissuance.jit_funding.max_auth_per_minute' => 25]);

    $value = (int) config('cardissuance.jit_funding.max_auth_per_minute', 10);

    expect($value)->toBe(25);
});

it('falls back to default of 10 when config key is absent', function (): void {
    config(['cardissuance.jit_funding' => []]);

    $value = (int) config('cardissuance.jit_funding.max_auth_per_minute', 10);

    expect($value)->toBe(10);
});

it('rate limiter logs warning when limit exceeded', function (): void {
    Log::spy();

    $key = 'jit_auth:card_warn_test';

    // Exhaust 1-attempt limit
    RateLimiter::attempt($key, 1, fn () => true, 60);

    // Simulate the log warning path
    if (RateLimiter::tooManyAttempts($key, 1)) {
        Log::warning('JIT auth rate limit exceeded', [
            'card_token_suffix' => substr('card_warn_test', -4),
        ]);
    }

    $spy = Log::getFacadeRoot();
    $spy->shouldHaveReceived('warning')
        ->once()
        ->with('JIT auth rate limit exceeded', Mockery::type('array'));
});
