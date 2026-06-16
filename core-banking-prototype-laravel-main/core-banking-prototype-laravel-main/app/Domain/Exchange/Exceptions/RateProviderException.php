<?php

declare(strict_types=1);

namespace App\Domain\Exchange\Exceptions;

use Exception;

class RateProviderException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = 'Rate provider error',
        int $code = 500,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
