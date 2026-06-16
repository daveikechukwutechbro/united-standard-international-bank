<?php

declare(strict_types=1);

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Contracts\OnChainSbtServiceInterface;

/**
 * In-memory demo implementation of OnChainSbtServiceInterface.
 *
 * Returns deterministic fake transaction hashes and addresses
 * for development and testing without requiring a live blockchain.
 */
class DemoOnChainSbtService implements OnChainSbtServiceInterface
{
    private int $nextTokenId = 1;

    /** @var array<string, array<int, array{uri: string, valid: bool}>> */
    private array $tokens = [];

    public function deployContract(string $name, string $symbol, string $baseUri): array
    {
        $contractAddress = '0x' . substr(hash('sha256', "deploy:{$name}:{$symbol}"), 0, 40);
        $txHash = '0x' . hash('sha256', "deploy_tx:{$name}:{$symbol}:{$baseUri}");

        return [
            'contract_address' => $contractAddress,
            'tx_hash'          => $txHash,
            'network'          => 'polygon-demo',
        ];
    }

    public function mintToken(
        string $contractAddress,
        string $recipientAddress,
        string $tokenUri,
        array $metadata = [],
    ): array {
        $tokenId = $this->nextTokenId++;
        $txHash = '0x' . hash('sha256', "mint_tx:{$contractAddress}:{$tokenId}:{$recipientAddress}");

        $this->tokens[$contractAddress][$tokenId] = [
            'uri'   => $tokenUri,
            'valid' => true,
        ];

        return [
            'token_id'         => $tokenId,
            'tx_hash'          => $txHash,
            'contract_address' => $contractAddress,
            'network'          => 'polygon-demo',
        ];
    }

    public function revokeToken(string $contractAddress, int $tokenId): array
    {
        $txHash = '0x' . hash('sha256', "revoke_tx:{$contractAddress}:{$tokenId}");

        if (isset($this->tokens[$contractAddress][$tokenId])) {
            $this->tokens[$contractAddress][$tokenId]['valid'] = false;
        }

        return [
            'tx_hash'          => $txHash,
            'contract_address' => $contractAddress,
            'network'          => 'polygon-demo',
        ];
    }

    public function isTokenValid(string $contractAddress, int $tokenId): bool
    {
        return $this->tokens[$contractAddress][$tokenId]['valid'] ?? false;
    }

    public function getTokenUri(string $contractAddress, int $tokenId): string
    {
        return $this->tokens[$contractAddress][$tokenId]['uri'] ?? '';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
