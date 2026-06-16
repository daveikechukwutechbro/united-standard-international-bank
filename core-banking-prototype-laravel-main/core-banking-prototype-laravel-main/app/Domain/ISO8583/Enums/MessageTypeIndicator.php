<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Enums;

enum MessageTypeIndicator: string
{
    case AUTH_REQUEST = '0100';
    case AUTH_RESPONSE = '0110';
    case FINANCIAL_REQUEST = '0200';
    case FINANCIAL_RESPONSE = '0210';
    case REVERSAL_REQUEST = '0400';
    case REVERSAL_RESPONSE = '0410';
    case SETTLEMENT_REQUEST = '0500';
    case SETTLEMENT_RESPONSE = '0510';
    case NETWORK_MANAGEMENT = '0800';

    public function isRequest(): bool
    {
        return in_array($this, [
            self::AUTH_REQUEST,
            self::FINANCIAL_REQUEST,
            self::REVERSAL_REQUEST,
            self::SETTLEMENT_REQUEST,
            self::NETWORK_MANAGEMENT,
        ], true);
    }

    public function responseType(): ?self
    {
        return match ($this) {
            self::AUTH_REQUEST       => self::AUTH_RESPONSE,
            self::FINANCIAL_REQUEST  => self::FINANCIAL_RESPONSE,
            self::REVERSAL_REQUEST   => self::REVERSAL_RESPONSE,
            self::SETTLEMENT_REQUEST => self::SETTLEMENT_RESPONSE,
            default                  => null,
        };
    }
}
