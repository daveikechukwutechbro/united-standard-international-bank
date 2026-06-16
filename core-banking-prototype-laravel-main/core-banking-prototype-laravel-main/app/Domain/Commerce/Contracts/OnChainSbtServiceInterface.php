<?php

declare(strict_types=1);

namespace App\Domain\Commerce\Contracts;

/**
 * Interface for on-chain soulbound token operations.
 *
 * Implementations handle deployment, minting, revoking, and querying
 * of ERC-5192 soulbound tokens on EVM-compatible blockchains.
 */
interface OnChainSbtServiceInterface
{
    /**
     * Deploy a new SBT contract to the network.
     *
     * @param string $name Token collection name
     * @param string $symbol Token collection symbol
     * @param string $baseUri Base URI for token metadata
     * @return array{contract_address: string, tx_hash: string, network: string}
     */
    public function deployContract(string $name, string $symbol, string $baseUri): array;

    /**
     * Mint a soulbound token to a recipient address.
     *
     * @param string $contractAddress Deployed SBT contract address
     * @param string $recipientAddress Recipient wallet address
     * @param string $tokenUri Token metadata URI
     * @param array<string, mixed> $metadata Additional metadata for the mint
     * @return array{token_id: int, tx_hash: string, contract_address: string, network: string}
     */
    public function mintToken(
        string $contractAddress,
        string $recipientAddress,
        string $tokenUri,
        array $metadata = [],
    ): array;

    /**
     * Revoke (burn) a soulbound token.
     *
     * @param string $contractAddress SBT contract address
     * @param int $tokenId Token ID to revoke
     * @return array{tx_hash: string, contract_address: string, network: string}
     */
    public function revokeToken(string $contractAddress, int $tokenId): array;

    /**
     * Check if a token is still valid (not burned).
     *
     * @param string $contractAddress SBT contract address
     * @param int $tokenId Token ID to check
     */
    public function isTokenValid(string $contractAddress, int $tokenId): bool;

    /**
     * Get the token URI for a minted token.
     *
     * @param string $contractAddress SBT contract address
     * @param int $tokenId Token ID
     */
    public function getTokenUri(string $contractAddress, int $tokenId): string;

    /**
     * Check if the on-chain service is available and configured.
     */
    public function isAvailable(): bool;
}
