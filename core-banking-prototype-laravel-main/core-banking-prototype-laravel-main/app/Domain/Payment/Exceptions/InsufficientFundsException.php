<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

/**
 * Exception thrown when a payment operation fails due to insufficient funds.
 */
class InsufficientFundsException extends PaymentException
{
    public function __construct(
        public readonly string $accountId,
        public readonly int $requiredAmount,
        public readonly int $availableAmount,
        public readonly string $currency,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf(
                'Insufficient funds in account %s. Required: %d %s, Available: %d %s',
                $accountId,
                $requiredAmount,
                $currency,
                $availableAmount,
                $currency
            );
        }
        parent::__construct($message);
    }
}
