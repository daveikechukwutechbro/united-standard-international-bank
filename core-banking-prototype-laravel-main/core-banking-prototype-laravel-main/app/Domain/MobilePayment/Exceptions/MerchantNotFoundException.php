<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Exceptions;

use RuntimeException;

class MerchantNotFoundException extends RuntimeException
{
    public function __construct(string $merchantId)
    {
        parent::__construct("Merchant '{$merchantId}' not found.");
    }
}
