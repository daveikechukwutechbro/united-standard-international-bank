<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Enums;

enum AchReturnCode: string
{
    case R01 = 'R01';
    case R02 = 'R02';
    case R03 = 'R03';
    case R04 = 'R04';
    case R08 = 'R08';
    case R10 = 'R10';
    case R29 = 'R29';

    public function label(): string
    {
        return match ($this) {
            self::R01 => 'Insufficient Funds',
            self::R02 => 'Account Closed',
            self::R03 => 'No Account',
            self::R04 => 'Invalid Account Number',
            self::R08 => 'Payment Stopped',
            self::R10 => 'Customer Advises Unauthorized',
            self::R29 => 'Corporate Customer Advises Not Authorized',
        };
    }

    public function isRecoverable(): bool
    {
        return $this === self::R01;
    }
}
