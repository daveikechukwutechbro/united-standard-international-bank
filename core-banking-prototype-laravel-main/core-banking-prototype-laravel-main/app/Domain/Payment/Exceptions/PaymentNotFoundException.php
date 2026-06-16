<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

/**
 * Exception thrown when a payment is not found.
 */
class PaymentNotFoundException extends PaymentException
{
    public function __construct(
        public readonly string $paymentId,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf('Payment with ID %s not found', $paymentId);
        }
        parent::__construct($message);
    }
}
