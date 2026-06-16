<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Money extends DataObject implements DataObjectContract
{
    public function __construct(
        private int $amount
    ) {
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function invert(): self
    {
        return new self(
            amount: $this->amount * -1,
        );
    }

    /**
     * @return int[]
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
        ];
    }
}
