<?php

declare(strict_types=1);

namespace App\Domain\Custodian\Exceptions;

use Exception;

class CustodianNotFoundException extends Exception
{
    public function __construct(string $message = 'Custodian not found', int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
