<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Enums;

/**
 * Card network type.
 */
enum CardNetwork: string
{
    case VISA = 'visa';
    case MASTERCARD = 'mastercard';
}
