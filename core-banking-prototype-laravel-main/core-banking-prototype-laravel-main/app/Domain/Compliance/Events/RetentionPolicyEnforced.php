<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class RetentionPolicyEnforced extends ShouldBeStored
{
    public function __construct(
        public string $dataType,
        public string $action,
        public int $affectedCount,
        public bool $dryRun,
    ) {
    }
}
