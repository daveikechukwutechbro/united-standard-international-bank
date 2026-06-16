<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Enums;

/**
 * Payment Intent state machine.
 *
 * Flow: CREATED -> AWAITING_AUTH -> SUBMITTING -> PENDING -> CONFIRMED/FAILED
 * Cancel: CREATED|AWAITING_AUTH -> CANCELLED
 * Expiry: CREATED|AWAITING_AUTH -> EXPIRED
 */
enum PaymentIntentStatus: string
{
    case CREATED = 'created';
    case AWAITING_AUTH = 'awaiting_auth';
    case SUBMITTING = 'submitting';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::CREATED       => 'Created',
            self::AWAITING_AUTH => 'Awaiting Authorization',
            self::SUBMITTING    => 'Submitting',
            self::PENDING       => 'Pending',
            self::CONFIRMED     => 'Confirmed',
            self::FAILED        => 'Failed',
            self::CANCELLED     => 'Cancelled',
            self::EXPIRED       => 'Expired',
        };
    }

    public function canTransitionTo(PaymentIntentStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    /**
     * @return array<PaymentIntentStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::CREATED       => [self::AWAITING_AUTH, self::CANCELLED, self::EXPIRED],
            self::AWAITING_AUTH => [self::SUBMITTING, self::CANCELLED, self::EXPIRED],
            self::SUBMITTING    => [self::PENDING, self::FAILED],
            self::PENDING       => [self::CONFIRMED, self::FAILED],
            self::CONFIRMED     => [],
            self::FAILED        => [],
            self::CANCELLED     => [],
            self::EXPIRED       => [],
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CONFIRMED, self::FAILED, self::CANCELLED, self::EXPIRED], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::CREATED, self::AWAITING_AUTH], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::CREATED, self::AWAITING_AUTH, self::SUBMITTING, self::PENDING], true);
    }
}
