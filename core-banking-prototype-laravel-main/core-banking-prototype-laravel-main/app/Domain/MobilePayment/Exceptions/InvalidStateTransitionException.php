<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Exceptions;

use App\Domain\MobilePayment\Enums\PaymentIntentStatus;
use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        public readonly PaymentIntentStatus $from,
        public readonly PaymentIntentStatus $to,
    ) {
        parent::__construct(
            "Cannot transition payment intent from '{$from->value}' to '{$to->value}'."
        );
    }
}
