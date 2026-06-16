<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Enums;

/**
 * Types of activity feed items.
 */
enum ActivityItemType: string
{
    case MERCHANT_PAYMENT = 'merchant_payment';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case SHIELD = 'shield';
    case UNSHIELD = 'unshield';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANT_PAYMENT => 'Merchant Payment',
            self::TRANSFER_IN      => 'Received',
            self::TRANSFER_OUT     => 'Sent',
            self::SHIELD           => 'Shield',
            self::UNSHIELD         => 'Unshield',
        };
    }

    public function isOutflow(): bool
    {
        return in_array($this, [self::MERCHANT_PAYMENT, self::TRANSFER_OUT, self::SHIELD], true);
    }

    public function isInflow(): bool
    {
        return in_array($this, [self::TRANSFER_IN, self::UNSHIELD], true);
    }

    /**
     * Get the filter group for activity feed filtering.
     */
    public function filterGroup(): string
    {
        return $this->isOutflow() ? 'expenses' : 'income';
    }
}
