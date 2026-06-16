<?php

declare(strict_types=1);

namespace App\Domain\DeFi\ValueObjects;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionStatus;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;

final readonly class DeFiPosition
{
    public function __construct(
        public string $positionId,
        public DeFiProtocol $protocol,
        public DeFiPositionType $type,
        public DeFiPositionStatus $status,
        public CrossChainNetwork $chain,
        public string $asset,
        public string $amount,
        public string $valueUsd,
        public string $apy,
        public ?string $healthFactor = null,
    ) {
    }

    public function isAtRisk(): bool
    {
        if ($this->healthFactor === null) {
            return false;
        }

        return bccomp($this->healthFactor, '1.5', 2) < 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'position_id'   => $this->positionId,
            'protocol'      => $this->protocol->value,
            'type'          => $this->type->value,
            'status'        => $this->status->value,
            'chain'         => $this->chain->value,
            'asset'         => $this->asset,
            'amount'        => $this->amount,
            'value_usd'     => $this->valueUsd,
            'apy'           => $this->apy,
            'health_factor' => $this->healthFactor,
        ];
    }
}
