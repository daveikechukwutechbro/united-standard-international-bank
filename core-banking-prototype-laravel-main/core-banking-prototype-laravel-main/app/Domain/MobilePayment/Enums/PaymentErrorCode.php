<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Enums;

/**
 * Error codes for mobile payment operations.
 */
enum PaymentErrorCode: string
{
    case MERCHANT_UNREACHABLE = 'MERCHANT_UNREACHABLE';
    case WRONG_NETWORK = 'WRONG_NETWORK';
    case WRONG_TOKEN = 'WRONG_TOKEN';
    case INSUFFICIENT_FEES = 'INSUFFICIENT_FEES';
    case INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    case NETWORK_BUSY = 'NETWORK_BUSY';
    case INTENT_EXPIRED = 'INTENT_EXPIRED';
    case INTENT_ALREADY_SUBMITTED = 'INTENT_ALREADY_SUBMITTED';

    public function httpStatus(): int
    {
        return match ($this) {
            self::MERCHANT_UNREACHABLE,
            self::WRONG_NETWORK,
            self::WRONG_TOKEN,
            self::INSUFFICIENT_FEES,
            self::INSUFFICIENT_FUNDS => 422,
            self::NETWORK_BUSY       => 200,
            self::INTENT_EXPIRED,
            self::INTENT_ALREADY_SUBMITTED => 409,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::MERCHANT_UNREACHABLE     => 'We couldn\'t connect to the merchant terminal.',
            self::WRONG_NETWORK            => 'The merchant does not accept payments on this network.',
            self::WRONG_TOKEN              => 'The merchant does not accept this token.',
            self::INSUFFICIENT_FEES        => 'Not enough native token to cover network fees.',
            self::INSUFFICIENT_FUNDS       => 'Insufficient token balance for this payment.',
            self::NETWORK_BUSY             => 'The network is experiencing high traffic. Your transaction may take longer than usual.',
            self::INTENT_EXPIRED           => 'This payment intent has expired. Please create a new one.',
            self::INTENT_ALREADY_SUBMITTED => 'This payment has already been submitted.',
        };
    }

    public function isInformational(): bool
    {
        return $this === self::NETWORK_BUSY;
    }
}
