<?php

declare(strict_types=1);

namespace App\Infrastructure\Web3;

use App\Infrastructure\Monitoring\MetricsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * JSON-RPC client for Ethereum-compatible chains.
 *
 * Provides eth_call and eth_getTransactionReceipt methods with:
 * - Automatic RPC URL resolution from crosschain/defi/web3 config
 * - Circuit breaker pattern (opens after 3 failures, resets after 5 minutes)
 * - Retry with backoff (3 attempts, 1s delay)
 * - Structured logging for debugging
 */
class EthRpcClient
{
    /** @var int Circuit breaker TTL in seconds (5 minutes) */
    private const CIRCUIT_BREAKER_TTL = 300;

    /** @var int Maximum failures before circuit breaker opens */
    private const MAX_FAILURES = 3;

    /** @var int HTTP timeout in seconds */
    private const HTTP_TIMEOUT = 10;

    /** @var int Number of retry attempts */
    private const RETRY_ATTEMPTS = 3;

    /** @var int Retry delay in milliseconds */
    private const RETRY_DELAY_MS = 1000;

    /** @var int HTTP timeout in seconds for sendTransaction (higher latency expected) */
    private const SEND_TX_TIMEOUT = 30;

    /**
     * Execute an eth_call against the specified chain's RPC endpoint.
     *
     * @param  string $to    Contract address to call
     * @param  string $data  ABI-encoded calldata (hex)
     * @param  string $chain Chain identifier (e.g. 'ethereum', 'polygon')
     * @return string Hex-encoded response data
     *
     * @throws RuntimeException If RPC URL is not configured, circuit breaker is open, or call fails
     */
    public function ethCall(string $to, string $data, string $chain): string
    {
        $rpcUrl = $this->getRpcUrl($chain);
        if ($rpcUrl === null) {
            throw new RuntimeException("No RPC URL configured for chain: {$chain}");
        }

        $this->checkCircuitBreaker($chain);

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS)
            ->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'method'  => 'eth_call',
                'params'  => [['to' => $to, 'data' => $data], 'latest'],
                'id'      => 1,
            ]);

        if ($response->failed()) {
            $this->recordFailure($chain);

            Log::error('EthRpcClient: HTTP request failed', [
                'chain'  => $chain,
                'to'     => $to,
                'status' => $response->status(),
            ]);

            throw new RuntimeException("RPC call failed for chain: {$chain}");
        }

        $result = $response->json();

        if (isset($result['error'])) {
            Log::warning('EthRpcClient: RPC error', [
                'chain' => $chain,
                'to'    => $to,
                'error' => $result['error'],
            ]);

            throw new RuntimeException('RPC error: ' . (string) ($result['error']['message'] ?? 'Unknown'));
        }

        $this->recordSuccess($chain);

        return (string) ($result['result'] ?? '0x');
    }

    /**
     * Send a transaction via eth_sendTransaction.
     *
     * @param  string $from   Sender address
     * @param  string $to     Contract address
     * @param  string $data   ABI-encoded calldata (hex)
     * @param  string $chain  Chain identifier
     * @return string Transaction hash
     *
     * @throws RuntimeException If RPC URL is not configured or transaction fails
     */
    public function sendTransaction(string $from, string $to, string $data, string $chain): string
    {
        $rpcUrl = $this->getRpcUrl($chain);
        if ($rpcUrl === null) {
            throw new RuntimeException("No RPC URL configured for chain: {$chain}");
        }

        $this->checkCircuitBreaker($chain);

        $response = Http::timeout(self::SEND_TX_TIMEOUT)
            ->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS)
            ->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'method'  => 'eth_sendTransaction',
                'params'  => [['from' => $from, 'to' => $to, 'data' => $data]],
                'id'      => 1,
            ]);

        if ($response->failed()) {
            $this->recordFailure($chain);

            throw new RuntimeException("Transaction submission failed for chain: {$chain}");
        }

        $result = $response->json();

        if (isset($result['error'])) {
            throw new RuntimeException('Transaction error: ' . (string) ($result['error']['message'] ?? 'Unknown'));
        }

        $this->recordSuccess($chain);

        return (string) ($result['result'] ?? '');
    }

    /**
     * Retrieve a transaction receipt by hash.
     *
     * @param  string     $txHash Transaction hash
     * @param  string     $chain  Chain identifier
     * @return array<string, mixed>|null Receipt data, or null if not found/pending
     */
    public function getTransactionReceipt(string $txHash, string $chain): ?array
    {
        $rpcUrl = $this->getRpcUrl($chain);
        if ($rpcUrl === null) {
            return null;
        }

        $this->checkCircuitBreaker($chain);

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'method'  => 'eth_getTransactionReceipt',
                'params'  => [$txHash],
                'id'      => 1,
            ]);

        if ($response->failed()) {
            $this->recordFailure($chain);

            return null;
        }

        $this->recordSuccess($chain);

        /** @var array<string, mixed>|null */
        return $response->json('result');
    }

    /**
     * Resolve the RPC URL for a chain from config.
     *
     * Checks web3, crosschain, and defi config namespaces in order.
     */
    private function getRpcUrl(string $chain): ?string
    {
        /** @var string|null $url */
        $url = config("web3.rpc_urls.{$chain}")
            ?? config("crosschain.rpc_urls.{$chain}")
            ?? config("defi.rpc_urls.{$chain}");

        if ($url === null || $url === '') {
            return null;
        }

        return $url;
    }

    /**
     * Check whether the circuit breaker is open for a given chain.
     *
     * @throws RuntimeException If the circuit breaker is open (too many recent failures)
     */
    private function checkCircuitBreaker(string $chain): void
    {
        $failures = (int) Cache::get("eth_rpc_failures:{$chain}", 0);

        if ($failures >= self::MAX_FAILURES) {
            Log::warning('EthRpcClient: Circuit breaker open', [
                'chain'    => $chain,
                'failures' => $failures,
            ]);

            app(MetricsService::class)->increment('circuit_breaker_trip', 1, ['chain' => $chain]);

            throw new RuntimeException("Circuit breaker open for chain: {$chain}");
        }
    }

    /**
     * Record a failure for circuit breaker tracking.
     */
    private function recordFailure(string $chain): void
    {
        $key = "eth_rpc_failures:{$chain}";
        Cache::add($key, 0, self::CIRCUIT_BREAKER_TTL);
        Cache::increment($key);
    }

    /**
     * Record a success (resets circuit breaker for the chain).
     */
    private function recordSuccess(string $chain): void
    {
        Cache::forget("eth_rpc_failures:{$chain}");
    }
}
