<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

/**
 * Exception thrown when an invalid or unsupported payment method is used.
 */
class InvalidPaymentMethodException extends PaymentException
{
    public function __construct(
        public readonly string $paymentMethod,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf('Invalid or unsupported payment method: %s', $paymentMethod);
        }
        parent::__construct($message);
    }
}
