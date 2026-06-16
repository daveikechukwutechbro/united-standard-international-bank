<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionStatus;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\ValueObjects\DeFiPosition;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Tracks user DeFi positions across protocols.
 */
class DeFiPositionTrackerService
{
    private const CACHE_PREFIX = 'defi_pos:';

    private const CACHE_TTL = 86400;

    /**
     * Record a new DeFi position.
     */
    public function openPosition(
        DeFiProtocol $protocol,
        DeFiPositionType $type,
        CrossChainNetwork $chain,
        string $asset,
        string $amount,
        string $valueUsd,
        string $apy,
        string $walletAddress,
        ?string $healthFactor = null,
    ): DeFiPosition {
        $positionId = 'pos-' . Str::uuid()->toString();

        $position = new DeFiPosition(
            positionId: $positionId,
            protocol: $protocol,
            type: $type,
            status: DeFiPositionStatus::ACTIVE,
            chain: $chain,
            asset: $asset,
            amount: $amount,
            valueUsd: $valueUsd,
            apy: $apy,
            healthFactor: $healthFactor,
        );

        $this->storePosition($walletAddress, $position);

        return $position;
    }

    /**
     * Close a position.
     *
     * @throws InvalidArgumentException if position not found
     */
    public function closePosition(string $walletAddress, string $positionId): void
    {
        $positions = $this->getPositionsFromCache($walletAddress);
        $found = false;

        foreach ($positions as $i => $data) {
            if ($data['position_id'] === $positionId) {
                $positions[$i]['status'] = DeFiPositionStatus::CLOSED->value;
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new InvalidArgumentException("Position {$positionId} not found for wallet {$walletAddress}.");
        }

        Cache::put(self::CACHE_PREFIX . $walletAddress, $positions, self::CACHE_TTL);
    }

    /**
     * Get all active positions for a wallet.
     *
     * @return array<DeFiPosition>
     */
    public function getActivePositions(
        string $walletAddress,
        ?CrossChainNetwork $chain = null,
        ?DeFiProtocol $protocol = null,
        ?DeFiPositionType $type = null,
    ): array {
        $positions = $this->getPositionsFromCache($walletAddress);

        return array_values(array_filter(
            array_filter(
                array_map(fn (array $data) => $this->hydratePosition($data), $positions),
            ),
            function (DeFiPosition $pos) use ($chain, $protocol, $type) {
                if ($pos->status !== DeFiPositionStatus::ACTIVE) {
                    return false;
                }
                if ($chain !== null && $pos->chain !== $chain) {
                    return false;
                }
                if ($protocol !== null && $pos->protocol !== $protocol) {
                    return false;
                }
                if ($type !== null && $pos->type !== $type) {
                    return false;
                }

                return true;
            },
        ));
    }

    /**
     * Get positions at risk (health factor < 1.5).
     *
     * @return array<DeFiPosition>
     */
    public function getAtRiskPositions(string $walletAddress): array
    {
        return array_filter(
            $this->getActivePositions($walletAddress),
            fn (DeFiPosition $pos) => $pos->isAtRisk(),
        );
    }

    /**
     * Get total value of all active positions.
     */
    public function getTotalValue(string $walletAddress): string
    {
        $total = '0';

        foreach ($this->getActivePositions($walletAddress) as $position) {
            $total = bcadd($total, $position->valueUsd, 2);
        }

        return $total;
    }

    private function storePosition(string $walletAddress, DeFiPosition $position): void
    {
        $positions = $this->getPositionsFromCache($walletAddress);
        $positions[] = $position->toArray();

        Cache::put(self::CACHE_PREFIX . $walletAddress, $positions, self::CACHE_TTL);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function getPositionsFromCache(string $walletAddress): array
    {
        return Cache::get(self::CACHE_PREFIX . $walletAddress, []);
    }

    private function hydratePosition(array $data): ?DeFiPosition
    {
        $requiredKeys = ['position_id', 'protocol', 'type', 'status', 'chain', 'asset', 'amount', 'value_usd', 'apy'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                Log::warning('DeFi position cache entry missing required key', ['missing_key' => $key, 'data' => $data]);

                return null;
            }
        }

        return new DeFiPosition(
            positionId: $data['position_id'],
            protocol: DeFiProtocol::from($data['protocol']),
            type: DeFiPositionType::from($data['type']),
            status: DeFiPositionStatus::from($data['status']),
            chain: CrossChainNetwork::from($data['chain']),
            asset: $data['asset'],
            amount: $data['amount'],
            valueUsd: $data['value_usd'],
            apy: $data['apy'],
            healthFactor: $data['health_factor'] ?? null,
        );
    }
}
