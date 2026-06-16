<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Enums;

/**
 * Digital wallet type for push provisioning.
 */
enum WalletType: string
{
    case APPLE_PAY = 'apple_pay';
    case GOOGLE_PAY = 'google_pay';
}
