<?php

declare(strict_types=1);

namespace App\Domain\Privacy\ValueObjects;

use JsonSerializable;

/**
 * Immutable value object representing a Merkle proof path for a commitment.
 */
final readonly class MerklePath implements JsonSerializable
{
    /**
     * @param array<int, string> $siblings  Sibling hashes at each level of the tree
     * @param array<int, int>    $pathIndices  Direction indicators (0 = left, 1 = right) at each level
     */
    public function __construct(
        public string $commitment,
        public string $root,
        public string $network,
        public int $leafIndex,
        public array $siblings,
        public array $pathIndices,
    ) {
    }

    /**
     * Get the depth of this proof path.
     */
    public function getDepth(): int
    {
        return count($this->siblings);
    }

    /**
     * Check if this path is valid for a given tree depth.
     */
    public function isValidForDepth(int $treeDepth): bool
    {
        return $this->getDepth() === $treeDepth
            && count($this->pathIndices) === $treeDepth;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'commitment'   => $this->commitment,
            'root'         => $this->root,
            'network'      => $this->network,
            'leaf_index'   => $this->leafIndex,
            'siblings'     => $this->siblings,
            'path_indices' => $this->pathIndices,
            'proof_depth'  => $this->getDepth(),
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
            commitment: $data['commitment'],
            root: $data['root'],
            network: $data['network'],
            leafIndex: (int) $data['leaf_index'],
            siblings: $data['siblings'],
            pathIndices: $data['path_indices'],
        );
    }
}
