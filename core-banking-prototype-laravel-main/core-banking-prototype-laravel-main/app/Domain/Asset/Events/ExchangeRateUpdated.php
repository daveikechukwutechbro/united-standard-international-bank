<?php

declare(strict_types=1);

namespace App\Domain\Asset\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ExchangeRateUpdated extends ShouldBeStored
{
    public function __construct(
        public readonly string $fromAssetCode,
        public readonly string $toAssetCode,
        public readonly float $oldRate,
        public readonly float $newRate,
        public readonly string $source,
        public readonly ?array $metadata = null
    ) {
    }

    /**
     * Get the rate change percentage.
     */
    public function getChangePercentage(): float
    {
        if ($this->oldRate == 0) {
            return 0;
        }

        return (($this->newRate - $this->oldRate) / $this->oldRate) * 100;
    }

    /**
     * Check if the rate increased.
     */
    public function isIncrease(): bool
    {
        return $this->newRate > $this->oldRate;
    }

    /**
     * Check if the rate decreased.
     */
    public function isDecrease(): bool
    {
        return $this->newRate < $this->oldRate;
    }

    /**
     * Check if this is a significant change (> 5%).
     */
    public function isSignificantChange(): bool
    {
        return abs($this->getChangePercentage()) > 5.0;
    }
}
