<?php

namespace App\Domain\Cgo\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class RefundCancelled extends ShouldBeStored
{
    public static string $queue = 'events';

    public function __construct(
        public readonly string $refundId,
        public readonly string $cancellationReason,
        public readonly string $cancelledBy,
        public readonly string $cancelledAt,
        public readonly array $metadata = []
    ) {
    }
}
