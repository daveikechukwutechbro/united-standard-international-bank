<?php

declare(strict_types=1);

use App\Domain\MobilePayment\Enums\PaymentIntentStatus;

describe('PaymentIntentStatus Enum', function (): void {
    it('has all expected statuses', function (): void {
        $statuses = PaymentIntentStatus::cases();

        expect($statuses)->toHaveCount(8);
        expect(PaymentIntentStatus::CREATED->value)->toBe('created');
        expect(PaymentIntentStatus::AWAITING_AUTH->value)->toBe('awaiting_auth');
        expect(PaymentIntentStatus::SUBMITTING->value)->toBe('submitting');
        expect(PaymentIntentStatus::PENDING->value)->toBe('pending');
        expect(PaymentIntentStatus::CONFIRMED->value)->toBe('confirmed');
        expect(PaymentIntentStatus::FAILED->value)->toBe('failed');
        expect(PaymentIntentStatus::CANCELLED->value)->toBe('cancelled');
        expect(PaymentIntentStatus::EXPIRED->value)->toBe('expired');
    });

    it('returns correct labels', function (): void {
        expect(PaymentIntentStatus::CREATED->label())->toBe('Created');
        expect(PaymentIntentStatus::AWAITING_AUTH->label())->toBe('Awaiting Authorization');
        expect(PaymentIntentStatus::CONFIRMED->label())->toBe('Confirmed');
    });

    it('validates forward transitions correctly', function (): void {
        // CREATED -> AWAITING_AUTH
        expect(PaymentIntentStatus::CREATED->canTransitionTo(PaymentIntentStatus::AWAITING_AUTH))->toBeTrue();
        expect(PaymentIntentStatus::CREATED->canTransitionTo(PaymentIntentStatus::CANCELLED))->toBeTrue();
        expect(PaymentIntentStatus::CREATED->canTransitionTo(PaymentIntentStatus::EXPIRED))->toBeTrue();
        expect(PaymentIntentStatus::CREATED->canTransitionTo(PaymentIntentStatus::CONFIRMED))->toBeFalse();

        // AWAITING_AUTH -> SUBMITTING or CANCELLED
        expect(PaymentIntentStatus::AWAITING_AUTH->canTransitionTo(PaymentIntentStatus::SUBMITTING))->toBeTrue();
        expect(PaymentIntentStatus::AWAITING_AUTH->canTransitionTo(PaymentIntentStatus::CANCELLED))->toBeTrue();
        expect(PaymentIntentStatus::AWAITING_AUTH->canTransitionTo(PaymentIntentStatus::CONFIRMED))->toBeFalse();

        // SUBMITTING -> PENDING or FAILED
        expect(PaymentIntentStatus::SUBMITTING->canTransitionTo(PaymentIntentStatus::PENDING))->toBeTrue();
        expect(PaymentIntentStatus::SUBMITTING->canTransitionTo(PaymentIntentStatus::FAILED))->toBeTrue();
        expect(PaymentIntentStatus::SUBMITTING->canTransitionTo(PaymentIntentStatus::CANCELLED))->toBeFalse();

        // PENDING -> CONFIRMED or FAILED
        expect(PaymentIntentStatus::PENDING->canTransitionTo(PaymentIntentStatus::CONFIRMED))->toBeTrue();
        expect(PaymentIntentStatus::PENDING->canTransitionTo(PaymentIntentStatus::FAILED))->toBeTrue();
        expect(PaymentIntentStatus::PENDING->canTransitionTo(PaymentIntentStatus::CANCELLED))->toBeFalse();
    });

    it('prevents transitions from final states', function (): void {
        foreach ([PaymentIntentStatus::CONFIRMED, PaymentIntentStatus::FAILED, PaymentIntentStatus::CANCELLED, PaymentIntentStatus::EXPIRED] as $final) {
            expect($final->allowedTransitions())->toBeEmpty();
        }
    });

    it('correctly identifies final states', function (): void {
        expect(PaymentIntentStatus::CONFIRMED->isFinal())->toBeTrue();
        expect(PaymentIntentStatus::FAILED->isFinal())->toBeTrue();
        expect(PaymentIntentStatus::CANCELLED->isFinal())->toBeTrue();
        expect(PaymentIntentStatus::EXPIRED->isFinal())->toBeTrue();

        expect(PaymentIntentStatus::CREATED->isFinal())->toBeFalse();
        expect(PaymentIntentStatus::PENDING->isFinal())->toBeFalse();
    });

    it('correctly identifies cancellable states', function (): void {
        expect(PaymentIntentStatus::CREATED->isCancellable())->toBeTrue();
        expect(PaymentIntentStatus::AWAITING_AUTH->isCancellable())->toBeTrue();

        expect(PaymentIntentStatus::SUBMITTING->isCancellable())->toBeFalse();
        expect(PaymentIntentStatus::PENDING->isCancellable())->toBeFalse();
        expect(PaymentIntentStatus::CONFIRMED->isCancellable())->toBeFalse();
    });

    it('correctly identifies active states', function (): void {
        expect(PaymentIntentStatus::CREATED->isActive())->toBeTrue();
        expect(PaymentIntentStatus::AWAITING_AUTH->isActive())->toBeTrue();
        expect(PaymentIntentStatus::SUBMITTING->isActive())->toBeTrue();
        expect(PaymentIntentStatus::PENDING->isActive())->toBeTrue();

        expect(PaymentIntentStatus::CONFIRMED->isActive())->toBeFalse();
        expect(PaymentIntentStatus::FAILED->isActive())->toBeFalse();
    });

    it('returns allowed transitions', function (): void {
        $transitions = PaymentIntentStatus::CREATED->allowedTransitions();
        expect($transitions)->toContain(PaymentIntentStatus::AWAITING_AUTH);
        expect($transitions)->toContain(PaymentIntentStatus::CANCELLED);
        expect($transitions)->toContain(PaymentIntentStatus::EXPIRED);
        expect($transitions)->toHaveCount(3);
    });
});
