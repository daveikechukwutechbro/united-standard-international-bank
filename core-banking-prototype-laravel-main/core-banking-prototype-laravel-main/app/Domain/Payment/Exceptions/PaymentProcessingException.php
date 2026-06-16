<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

/**
 * Exception thrown when payment processing fails.
 */
class PaymentProcessingException extends PaymentException
{
    public function __construct(
        public readonly string $paymentMethod,
        public readonly ?string $externalReference = null,
        string $message = '',
        public readonly ?string $providerError = null
    ) {
        if ($message === '') {
            $message = sprintf('Payment processing failed for method %s', $paymentMethod);
        }
        parent::__construct($message);
    }
}
