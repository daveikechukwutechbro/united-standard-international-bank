<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Contracts;

use App\Domain\Privacy\ValueObjects\MerklePath;
use App\Domain\Privacy\ValueObjects\MerkleRoot;

/**
 * Interface for Merkle tree operations for privacy pool commitments.
 */
interface MerkleTreeServiceInterface
{
    /**
     * Get the current Merkle root for a network.
     */
    public function getMerkleRoot(string $network): MerkleRoot;

    /**
     * Get the Merkle proof path for a commitment.
     *
     * @throws \App\Domain\Privacy\Exceptions\CommitmentNotFoundException
     */
    public function getMerklePath(string $commitment, string $network): MerklePath;

    /**
     * Verify that a commitment exists in the tree with the given proof.
     */
    public function verifyCommitment(string $commitment, MerklePath $path): bool;

    /**
     * Check if a network is supported.
     */
    public function supportsNetwork(string $network): bool;

    /**
     * Get all supported networks.
     *
     * @return array<string>
     */
    public function getSupportedNetworks(): array;

    /**
     * Trigger a sync of the Merkle tree from the blockchain.
     */
    public function syncTree(string $network): MerkleRoot;

    /**
     * Get the tree depth for the privacy pool.
     */
    public function getTreeDepth(): int;

    /**
     * Get the service provider name.
     */
    public function getProviderName(): string;
}
