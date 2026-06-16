<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

/**
 * Exception thrown when a payment cancellation fails.
 */
class PaymentCancellationException extends PaymentException
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $reason,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf('Cannot cancel payment %s: %s', $paymentId, $reason);
        }
        parent::__construct($message);
    }
}
