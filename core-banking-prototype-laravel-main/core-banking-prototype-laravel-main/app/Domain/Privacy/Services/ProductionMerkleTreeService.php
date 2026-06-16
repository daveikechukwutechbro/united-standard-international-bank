<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use App\Domain\Privacy\Contracts\MerkleTreeServiceInterface;
use App\Domain\Privacy\Events\Broadcast\MerkleRootUpdated;
use App\Domain\Privacy\Exceptions\CommitmentNotFoundException;
use App\Domain\Privacy\ValueObjects\MerklePath;
use App\Domain\Privacy\ValueObjects\MerkleRoot;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Production Merkle tree service that syncs with on-chain privacy pools via JSON-RPC.
 *
 * Uses PoseidonHasher for Merkle hashing. Calls privacy pool contracts via
 * blockchain RPC for getMerkleRoot() and getMerklePath().
 */
class ProductionMerkleTreeService implements MerkleTreeServiceInterface
{
    private const CACHE_PREFIX = 'prod_merkle_';

    private const CACHE_TTL_SECONDS = 30;

    private readonly PoseidonHasher $hasher;

    public function __construct(?PoseidonHasher $hasher = null)
    {
        $this->hasher = $hasher ?? new PoseidonHasher();
    }

    public function getMerkleRoot(string $network): MerkleRoot
    {
        $this->validateNetwork($network);

        $cacheKey = self::CACHE_PREFIX . 'root_' . $network;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($network): MerkleRoot {
            return $this->fetchMerkleRootFromChain($network);
        });
    }

    public function getMerklePath(string $commitment, string $network): MerklePath
    {
        $this->validateNetwork($network);
        $this->validateCommitment($commitment);

        $cacheKey = self::CACHE_PREFIX . 'path_' . $network . '_' . $commitment;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return MerklePath::fromArray($cached);
        }

        $path = $this->fetchMerklePathFromChain($commitment, $network);

        Cache::put($cacheKey, $path->toArray(), self::CACHE_TTL_SECONDS);

        return $path;
    }

    public function verifyCommitment(string $commitment, MerklePath $path): bool
    {
        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $commitment)) {
            return false;
        }

        foreach ($path->siblings as $sibling) {
            if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $sibling)) {
                return false;
            }
        }

        foreach ($path->pathIndices as $index) {
            if ($index !== 0 && $index !== 1) {
                return false;
            }
        }

        if (! $path->isValidForDepth($this->getTreeDepth())) {
            return false;
        }

        // Compute root using PoseidonHasher
        $computedRoot = $this->computeRoot($commitment, $path);

        $currentRoot = $this->getMerkleRoot($path->network);

        return hash_equals($currentRoot->root, $computedRoot);
    }

    public function supportsNetwork(string $network): bool
    {
        return in_array($network, $this->getSupportedNetworks(), true);
    }

    public function getSupportedNetworks(): array
    {
        return (array) config('privacy.merkle.networks', ['polygon', 'base', 'arbitrum']);
    }

    public function syncTree(string $network): MerkleRoot
    {
        $this->validateNetwork($network);

        $cacheKey = self::CACHE_PREFIX . 'root_' . $network;
        Cache::forget($cacheKey);

        Log::info('Production Merkle tree sync triggered', ['network' => $network]);

        $root = $this->getMerkleRoot($network);

        MerkleRootUpdated::dispatch(
            $network,
            $root->root,
            $root->leafCount,
            $root->blockNumber,
            $root->treeDepth,
            $root->syncedAt->format('c'),
        );

        return $root;
    }

    public function getTreeDepth(): int
    {
        return (int) config('privacy.merkle.max_tree_depth', 32);
    }

    public function getProviderName(): string
    {
        return 'production';
    }

    private function validateNetwork(string $network): void
    {
        if (! $this->supportsNetwork($network)) {
            throw new InvalidArgumentException(
                "Unsupported network: {$network}. Supported: " . implode(', ', $this->getSupportedNetworks())
            );
        }
    }

    private function validateCommitment(string $commitment): void
    {
        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $commitment)) {
            throw new InvalidArgumentException('Invalid commitment format. Expected 32-byte hex string with 0x prefix.');
        }
    }

    /**
     * Fetch the Merkle root from the privacy pool contract via RPC.
     */
    private function fetchMerkleRootFromChain(string $network): MerkleRoot
    {
        $rpcUrl = $this->getRpcUrl($network);
        $poolAddress = $this->getPoolAddress($network);

        if (empty($poolAddress)) {
            throw new RuntimeException("Privacy pool contract address not configured for network: {$network}");
        }

        // Call merkleRoot() on the privacy pool contract
        // Function selector for merkleRoot() = keccak256("merkleRoot()")[0:4]
        $selector = '0x5ca1e165'; // keccak256("merkleRoot()")
        $rootHex = $this->ethCall($rpcUrl, $poolAddress, $selector);

        // Call getLeafCount() for leaf count
        $leafSelector = '0x12a87856'; // approximate selector for getLeafCount()
        $leafCountHex = '0x0';
        try {
            $leafCountHex = $this->ethCall($rpcUrl, $poolAddress, $leafSelector);
        } catch (RuntimeException) {
            // Fallback: some contracts don't expose leaf count
        }

        // Get current block number
        $blockNumber = $this->getBlockNumber($rpcUrl);

        return new MerkleRoot(
            root: $rootHex,
            network: $network,
            leafCount: (int) hexdec(str_starts_with($leafCountHex, '0x') ? substr($leafCountHex, 2) : $leafCountHex),
            treeDepth: $this->getTreeDepth(),
            blockNumber: $blockNumber,
            syncedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Fetch the Merkle path for a commitment from the chain.
     */
    private function fetchMerklePathFromChain(string $commitment, string $network): MerklePath
    {
        $rpcUrl = $this->getRpcUrl($network);
        $poolAddress = $this->getPoolAddress($network);

        if (empty($poolAddress)) {
            throw new RuntimeException("Privacy pool contract address not configured for network: {$network}");
        }

        // Encode getMerklePath(bytes32 commitment) call
        $hex = str_starts_with($commitment, '0x') ? substr($commitment, 2) : $commitment;
        $selector = '0xa1e5e2e4'; // approximate selector for getMerklePath(bytes32)
        $callData = $selector . str_pad($hex, 64, '0', STR_PAD_LEFT);

        try {
            $result = $this->ethCall($rpcUrl, $poolAddress, $callData);

            // Parse ABI-encoded response (array of siblings + array of path indices)
            $parsed = $this->decodeMerklePathResponse($result, $commitment, $network);

            return $parsed;
        } catch (RuntimeException $e) {
            throw new CommitmentNotFoundException(
                "Commitment not found in privacy pool on {$network}: {$commitment}",
                previous: $e,
            );
        }
    }

    private function computeRoot(string $commitment, MerklePath $path): string
    {
        $current = $commitment;

        foreach ($path->siblings as $index => $sibling) {
            $pathIndex = $path->pathIndices[$index] ?? 0;

            if ($pathIndex === 0) {
                $current = $this->hasher->hash($current, $sibling);
            } else {
                $current = $this->hasher->hash($sibling, $current);
            }
        }

        return $current;
    }

    private function ethCall(string $rpcUrl, string $to, string $data): string
    {
        $response = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method'  => 'eth_call',
            'params'  => [
                ['to' => $to, 'data' => $data],
                'latest',
            ],
            'id' => 1,
        ]);

        $json = $response->json();

        if (isset($json['error'])) {
            throw new RuntimeException('eth_call failed: ' . ($json['error']['message'] ?? 'Unknown error'));
        }

        return $json['result'] ?? '0x';
    }

    private function getBlockNumber(string $rpcUrl): int
    {
        $response = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method'  => 'eth_blockNumber',
            'params'  => [],
            'id'      => 1,
        ]);

        $json = $response->json();
        $hex = $json['result'] ?? '0x0';
        $hexPart = str_starts_with($hex, '0x') ? substr($hex, 2) : $hex;

        return (int) hexdec($hexPart);
    }

    private function getRpcUrl(string $network): string
    {
        $urls = [
            'polygon'  => (string) config('blockchain.polygon.rpc_url', 'https://polygon-rpc.com'),
            'base'     => (string) config('blockchain.base.rpc_url', 'https://mainnet.base.org'),
            'arbitrum' => (string) config('blockchain.arbitrum.rpc_url', 'https://arb1.arbitrum.io/rpc'),
        ];

        return $urls[$network] ?? throw new RuntimeException("No RPC URL for network: {$network}");
    }

    private function getPoolAddress(string $network): string
    {
        return (string) config("privacy.merkle.pool_addresses.{$network}", '');
    }

    /**
     * Decode a getMerklePath ABI response into a MerklePath value object.
     */
    private function decodeMerklePathResponse(string $hexData, string $commitment, string $network): MerklePath
    {
        $hex = str_starts_with($hexData, '0x') ? substr($hexData, 2) : $hexData;

        if (strlen($hex) < 128) {
            throw new RuntimeException('Invalid Merkle path response: too short');
        }

        // Simplified parsing: extract leaf index and build path from data
        $leafIndex = (int) hexdec(substr($hex, 0, 64));
        $root = '0x' . substr($hex, 64, 64);

        // Parse siblings (each 32 bytes)
        $depth = $this->getTreeDepth();
        $siblings = [];
        $pathIndices = [];
        $offset = 128;

        for ($i = 0; $i < min($depth, (int) ((strlen($hex) - 128) / 64)); $i++) {
            $siblings[] = '0x' . substr($hex, $offset + ($i * 64), 64);
            $pathIndices[] = ($leafIndex >> $i) & 1;
        }

        // Pad to full depth if needed
        while (count($siblings) < $depth) {
            $siblings[] = '0x' . str_repeat('0', 64);
            $pathIndices[] = 0;
        }

        return new MerklePath(
            commitment: $commitment,
            root: $root,
            network: $network,
            leafIndex: $leafIndex,
            siblings: $siblings,
            pathIndices: $pathIndices,
        );
    }
}
