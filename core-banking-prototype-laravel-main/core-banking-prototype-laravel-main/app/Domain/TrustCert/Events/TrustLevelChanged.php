<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use App\Domain\TrustCert\Enums\TrustLevel;
use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an issuer's trust level changes.
 */
class TrustLevelChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $issuerId,
        public readonly TrustLevel $previousLevel,
        public readonly TrustLevel $newLevel,
        public readonly DateTimeInterface $changedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'issuer_id'      => $this->issuerId,
            'previous_level' => $this->previousLevel->value,
            'new_level'      => $this->newLevel->value,
            'changed_at'     => $this->changedAt->format('c'),
        ];
    }
}
