<?php

declare(strict_types=1);

namespace App\Domain\Wallet\ValueObjects;

final class WalletAddress
{
    public function __construct(
        public readonly string $address,
        public readonly string $blockchain,
        public readonly ?string $label = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'address'    => $this->address,
            'blockchain' => $this->blockchain,
            'label'      => $this->label,
        ];
    }
}
