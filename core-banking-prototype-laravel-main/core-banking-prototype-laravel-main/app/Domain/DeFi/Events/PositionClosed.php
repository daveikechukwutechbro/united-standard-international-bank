<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Events;

use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use Illuminate\Foundation\Events\Dispatchable;

class PositionClosed
{
    use Dispatchable;

    public function __construct(
        public readonly string $positionId,
        public readonly DeFiProtocol $protocol,
        public readonly DeFiPositionType $type,
        public readonly string $reason,
    ) {
    }
}
