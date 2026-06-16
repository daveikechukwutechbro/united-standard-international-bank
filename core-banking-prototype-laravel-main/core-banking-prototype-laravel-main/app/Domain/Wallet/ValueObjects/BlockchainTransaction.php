<?php

declare(strict_types=1);

namespace App\Domain\Wallet\ValueObjects;

final class BlockchainTransaction
{
    public function __construct(
        public readonly string $hash,
        public readonly string $from,
        public readonly string $to,
        public readonly string $value,
        public readonly string $blockchain,
        public readonly string $status
    ) {
    }

    public function toArray(): array
    {
        return [
            'hash'       => $this->hash,
            'from'       => $this->from,
            'to'         => $this->to,
            'value'      => $this->value,
            'blockchain' => $this->blockchain,
            'status'     => $this->status,
        ];
    }
}
