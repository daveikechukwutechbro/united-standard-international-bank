<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use DateTimeImmutable;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AlertResolved extends ShouldBeStored
{
    public function __construct(
        public readonly string $alertId,
        public readonly string $resolution,
        public readonly string $resolvedBy,
        public readonly string $notes,
        public readonly DateTimeImmutable $resolvedAt
    ) {
    }
}
