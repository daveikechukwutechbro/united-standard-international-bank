<?php

declare(strict_types=1);

use App\Domain\Interledger\Services\IlpAddressResolver;
use Tests\TestCase;

uses(TestCase::class);

describe('IlpAddressResolver', function (): void {
    beforeEach(function (): void {
        $this->resolver = new IlpAddressResolver();
    });

    describe('resolve()', function (): void {
        it('returns a g.finaegis.user.{id} address by default', function (): void {
            $uuid = '550e8400-e29b-41d4-a716-446655440000';
            $address = $this->resolver->resolve($uuid);

            expect($address)->toBe('g.finaegis.user.' . $uuid);
        });

        it('uses the configured ILP address prefix', function (): void {
            config(['interledger.ilp_address' => 'g.testnet']);

            $address = $this->resolver->resolve('abc123');

            expect($address)->toStartWith('g.testnet.user.');
        });

        it('appends the account ID at the end of the address', function (): void {
            $accountId = 'my-account-42';
            $address = $this->resolver->resolve($accountId);

            expect($address)->toEndWith($accountId);
        });
    });

    describe('fromPaymentPointer()', function (): void {
        it('converts a simple payment pointer to an ILP address', function (): void {
            $address = $this->resolver->fromPaymentPointer('$wallet.example.com');

            expect($address)->toStartWith('g.');
            expect($address)->toContain('wallet.example.com');
        });

        it('incorporates the path segment when present', function (): void {
            $address = $this->resolver->fromPaymentPointer('$wallet.example.com/alice');

            expect($address)->toContain('alice');
        });

        it('returns the input unchanged when it is not a payment pointer', function (): void {
            $ilp = 'g.finaegis.user.abc';
            $address = $this->resolver->fromPaymentPointer($ilp);

            expect($address)->toBe($ilp);
        });
    });

    describe('toPaymentPointer()', function (): void {
        it('converts a g.finaegis.user.{id} address to a payment pointer', function (): void {
            $pointer = $this->resolver->toPaymentPointer('g.finaegis.user.abc123');

            expect($pointer)->toStartWith('$');
            expect($pointer)->toContain('finaegis');
        });

        it('produces a round-trip consistent pointer for simple addresses', function (): void {
            // resolve -> toPaymentPointer should be consistent
            $accountId = '1234';
            $ilp = $this->resolver->resolve($accountId);
            $pointer = $this->resolver->toPaymentPointer($ilp);

            expect($pointer)->toStartWith('$');
        });

        it('converts a basic ILP address to a payment pointer starting with $', function (): void {
            $pointer = $this->resolver->toPaymentPointer('g.example.user');

            expect($pointer)->toBe('$example/user');
        });
    });
});
