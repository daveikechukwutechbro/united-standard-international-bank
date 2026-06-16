<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Enums;

/**
 * JIT funding authorization decision.
 */
enum AuthorizationDecision: string
{
    case APPROVED = 'approved';
    case DECLINED_INSUFFICIENT_FUNDS = 'declined_insufficient_funds';
    case DECLINED_CARD_FROZEN = 'declined_card_frozen';
    case DECLINED_CARD_CANCELLED = 'declined_card_cancelled';
    case DECLINED_LIMIT_EXCEEDED = 'declined_limit_exceeded';
    case DECLINED_FRAUD_SUSPECTED = 'declined_fraud_suspected';
    case DECLINED_MERCHANT_BLOCKED = 'declined_merchant_blocked';

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function getMessage(): string
    {
        return match ($this) {
            self::APPROVED                    => 'Transaction approved',
            self::DECLINED_INSUFFICIENT_FUNDS => 'Insufficient stablecoin balance',
            self::DECLINED_CARD_FROZEN        => 'Card is frozen',
            self::DECLINED_CARD_CANCELLED     => 'Card has been cancelled',
            self::DECLINED_LIMIT_EXCEEDED     => 'Transaction limit exceeded',
            self::DECLINED_FRAUD_SUSPECTED    => 'Suspicious activity detected',
            self::DECLINED_MERCHANT_BLOCKED   => 'Merchant is blocked',
        };
    }
}
