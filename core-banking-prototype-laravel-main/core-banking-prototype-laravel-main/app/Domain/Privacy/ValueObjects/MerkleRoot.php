<?php

declare(strict_types=1);

namespace App\Domain\Privacy\ValueObjects;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Immutable value object representing a Merkle tree root for privacy pool commitments.
 */
final readonly class MerkleRoot implements JsonSerializable
{
    public function __construct(
        public string $root,
        public string $network,
        public int $leafCount,
        public int $treeDepth,
        public int $blockNumber,
        public DateTimeImmutable $syncedAt,
    ) {
    }

    /**
     * Check if the root is stale based on the given interval.
     */
    public function isStale(int $maxAgeSeconds): bool
    {
        $now = new DateTimeImmutable();
        $age = $now->getTimestamp() - $this->syncedAt->getTimestamp();

        return $age > $maxAgeSeconds;
    }

    /**
     * Get the age of this root in seconds.
     */
    public function getAgeSeconds(): int
    {
        $now = new DateTimeImmutable();

        return max(0, $now->getTimestamp() - $this->syncedAt->getTimestamp());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'root'         => $this->root,
            'network'      => $this->network,
            'leaf_count'   => $this->leafCount,
            'tree_depth'   => $this->treeDepth,
            'block_number' => $this->blockNumber,
            'synced_at'    => $this->syncedAt->format('c'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            root: $data['root'],
            network: $data['network'],
            leafCount: (int) $data['leaf_count'],
            treeDepth: (int) $data['tree_depth'],
            blockNumber: (int) $data['block_number'],
            syncedAt: new DateTimeImmutable($data['synced_at']),
        );
    }
}
