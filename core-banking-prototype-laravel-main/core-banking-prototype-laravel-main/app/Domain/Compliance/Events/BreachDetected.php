<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BreachDetected extends ShouldBeStored
{
    public function __construct(
        public string $breachId,
        public string $title,
        public string $severity,
        public string $discoveryTime,
        public string $notificationDeadline,
    ) {
    }
}
