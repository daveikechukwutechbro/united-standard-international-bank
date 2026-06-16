<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Constants;

final class SolanaCacheKeys
{
    public static function balance(string $address): string
    {
        return "solana_balance:{$address}";
    }

    public static function balances(string $address): string
    {
        return "solana_balances:{$address}";
    }

    public static function knownAddr(string $address): string
    {
        return "solana_known_addr:{$address}";
    }
}
