<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BreachAuthorityNotified extends ShouldBeStored
{
    public function __construct(
        public string $breachId,
        public string $notifiedAt,
        public ?string $notes = null,
    ) {
    }
}
