<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Exceptions;

use App\Domain\MobilePayment\Enums\PaymentErrorCode;
use RuntimeException;

class PaymentIntentException extends RuntimeException
{
    public function __construct(
        public readonly PaymentErrorCode $errorCode,
        ?string $message = null,
        /** @var array<string, mixed> */
        public readonly array $details = [],
    ) {
        parent::__construct($message ?? $errorCode->message());
    }

    public function httpStatus(): int
    {
        return $this->errorCode->httpStatus();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'success' => false,
            'error'   => [
                'code'    => $this->errorCode->value,
                'message' => $this->getMessage(),
            ],
        ];
    }
}
