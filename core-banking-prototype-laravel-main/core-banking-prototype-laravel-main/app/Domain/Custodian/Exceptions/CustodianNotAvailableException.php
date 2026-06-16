<?php

declare(strict_types=1);

namespace App\Domain\Custodian\Exceptions;

use Exception;

class CustodianNotAvailableException extends Exception
{
    public function __construct(string $message = 'Custodian is not available', int $code = 503, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
