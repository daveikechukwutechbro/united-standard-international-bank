<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ConsentRecorded extends ShouldBeStored
{
    public function __construct(
        public string $userUuid,
        public string $purpose,
        public bool $granted,
        public string $version,
    ) {
    }
}
