<?php

declare(strict_types=1);

namespace App\Domain\Basket\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BasketCreated extends ShouldBeStored
{
    public function __construct(
        public readonly string $basketCode,
        public readonly string $name,
        public readonly array $components,
        public readonly string $type,
        public readonly string $createdBy
    ) {
    }

    /**
     * Get the total weight of all components.
     */
    public function getTotalWeight(): float
    {
        return array_sum(array_column($this->components, 'weight'));
    }

    /**
     * Validate that component weights sum to 100%.
     */
    public function isValid(): bool
    {
        return abs($this->getTotalWeight() - 100) < 0.01;
    }
}
