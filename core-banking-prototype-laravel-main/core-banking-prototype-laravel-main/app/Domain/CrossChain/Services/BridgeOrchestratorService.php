<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Events\BridgeTransactionCompleted;
use App\Domain\CrossChain\Events\BridgeTransactionFailed;
use App\Domain\CrossChain\Events\BridgeTransactionInitiated;
use App\Domain\CrossChain\Exceptions\BridgeTransactionFailedException;
use App\Domain\CrossChain\Exceptions\UnsupportedBridgeRouteException;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BridgeOrchestratorService
{
    /** @var array<string, BridgeAdapterInterface> */
    private array $adapters = [];

    /**
     * Register a bridge adapter.
     */
    public function registerAdapter(BridgeAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getProvider()->value] = $adapter;
    }

    /**
     * Get all registered adapters.
     *
     * @return array<string, BridgeAdapterInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Get bridge quotes from all adapters that support the route.
     *
     * Quotes are stored server-side in cache (keyed by quote ID) so that
     * clients cannot tamper with quote data before calling initiateBridge().
     *
     * @return array<BridgeQuote>
     * @throws UnsupportedBridgeRouteException
     */
    public function getQuotes(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $quotes = [];

        foreach ($this->adapters as $adapter) {
            if (! $adapter->supportsRoute($sourceChain, $destChain, $token)) {
                continue;
            }

            try {
                $quote = $adapter->getQuote($sourceChain, $destChain, $token, $amount);
                // Store quote server-side to prevent client tampering (Finding #3)
                Cache::put("bridge_quote:{$quote->quoteId}", $quote->toArray(), 60);
                $quotes[] = $quote;
            } catch (Throwable $e) {
                Log::warning('Bridge adapter quote failed', [
                    'provider' => $adapter->getProvider()->value,
                    'source'   => $sourceChain->value,
                    'dest'     => $destChain->value,
                    'token'    => $token,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if (empty($quotes)) {
            throw UnsupportedBridgeRouteException::forRoute($sourceChain, $destChain, $token);
        }

        return $quotes;
    }

    /**
     * Get the best quote (lowest fee) for a bridge transfer.
     *
     * @throws UnsupportedBridgeRouteException
     */
    public function getBestQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $quotes = $this->getQuotes($sourceChain, $destChain, $token, $amount);

        usort($quotes, fn (BridgeQuote $a, BridgeQuote $b) => bccomp($a->fee, $b->fee, 18));

        return $quotes[0];
    }

    /**
     * Get the fastest quote for a bridge transfer.
     *
     * @throws UnsupportedBridgeRouteException
     */
    public function getFastestQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $quotes = $this->getQuotes($sourceChain, $destChain, $token, $amount);

        usort($quotes, fn (BridgeQuote $a, BridgeQuote $b) => $a->estimatedTimeSeconds <=> $b->estimatedTimeSeconds);

        return $quotes[0];
    }

    /**
     * Initiate a bridge transfer by referencing a server-side cached quote.
     *
     * Security controls applied (Findings #3, #10, #14):
     *   - Quote is fetched from server-side cache to prevent client tampering.
     *   - Sender address is verified against the user's registered wallets.
     *   - Amount is validated against per-transaction and daily limits.
     *
     * @return array{transaction_id: string, status: BridgeStatus}
     * @throws BridgeTransactionFailedException
     * @throws RuntimeException
     */
    public function initiateBridge(
        string $quoteId,
        string $senderAddress,
        string $recipientAddress,
        string $userUuid,
    ): array {
        // Finding #3: fetch quote from server-side cache; reject if missing/expired
        /** @var array<string, mixed>|null $quoteData */
        $quoteData = Cache::get("bridge_quote:{$quoteId}");

        if ($quoteData === null) {
            throw new RuntimeException('Quote expired or invalid');
        }

        $quote = $this->hydrateQuote($quoteData);

        if ($quote->isExpired()) {
            Cache::forget("bridge_quote:{$quoteId}");
            throw BridgeTransactionFailedException::quoteExpired($quote->quoteId);
        }

        // Finding #14: enforce per-transaction and daily bridge limits (cache-only, cheap)
        $this->enforceValueLimits($quote->inputAmount, $userUuid);

        // Finding #10: verify sender address belongs to the authenticated user
        $ownsAddress = BlockchainAddress::where('address', $senderAddress)
            ->where('user_uuid', $userUuid)
            ->where('is_active', true)
            ->exists();

        if (! $ownsAddress) {
            throw new RuntimeException('Sender address is not registered to this user');
        }

        $adapter = $this->getAdapterForProvider($quote->getProvider());

        try {
            $result = $adapter->initiateBridge($quote, $senderAddress, $recipientAddress);

            // Consume the quote so it cannot be replayed
            Cache::forget("bridge_quote:{$quoteId}");

            // Record daily volume/count atomically (Finding #14)
            $date = date('Y-m-d');
            $dailyVolumeKey = "bridge_daily_volume:{$userUuid}:{$date}";
            $dailyCountKey = "bridge_daily_count:{$userUuid}:{$date}";
            $ttl = 86400;

            Cache::add($dailyVolumeKey, 0, $ttl);
            Cache::increment($dailyVolumeKey, (int) round((float) $quote->inputAmount * 100));

            Cache::add($dailyCountKey, 0, $ttl);
            Cache::increment($dailyCountKey);

            BridgeTransactionInitiated::dispatch(
                $result['transaction_id'],
                $quote->getSourceChain(),
                $quote->getDestChain(),
                $quote->route->token,
                $quote->inputAmount,
                $quote->getProvider(),
                $senderAddress,
                $recipientAddress,
            );

            Log::info('Bridge transaction initiated', [
                'transaction_id' => $result['transaction_id'],
                'provider'       => $quote->getProvider()->value,
                'source'         => $quote->getSourceChain()->value,
                'dest'           => $quote->getDestChain()->value,
                'token'          => $quote->route->token,
                'amount'         => $quote->inputAmount,
            ]);

            return [
                'transaction_id' => $result['transaction_id'],
                'status'         => $result['status'],
            ];
        } catch (BridgeTransactionFailedException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw BridgeTransactionFailedException::executionFailed(
                'unknown',
                $e->getMessage(),
                $e,
            );
        }
    }

    /**
     * Enforce per-transaction and daily volume/count limits (Finding #14).
     *
     * @throws RuntimeException
     */
    private function enforceValueLimits(string $inputAmount, string $userUuid): void
    {
        $limits = config('crosschain.bridge_limits');
        $amount = (float) $inputAmount;
        $date = date('Y-m-d');

        /** @var float $maxPerTx */
        $maxPerTx = $limits['max_per_transaction'] ?? 100000.00;

        if ($amount > $maxPerTx) {
            throw new RuntimeException(
                "Bridge amount exceeds per-transaction limit of {$maxPerTx}"
            );
        }

        /** @var float $maxDailyVolume */
        $maxDailyVolume = $limits['max_daily_volume'] ?? 500000.00;

        /** @var int $currentVolumeRaw */
        $currentVolumeRaw = (int) Cache::get("bridge_daily_volume:{$userUuid}:{$date}", 0);
        $currentVolume = $currentVolumeRaw / 100;

        if (($currentVolume + $amount) > $maxDailyVolume) {
            throw new RuntimeException(
                "Bridge amount would exceed daily volume limit of {$maxDailyVolume}"
            );
        }

        /** @var int $maxDailyCount */
        $maxDailyCount = $limits['max_daily_count'] ?? 50;

        /** @var int $currentCount */
        $currentCount = (int) Cache::get("bridge_daily_count:{$userUuid}:{$date}", 0);

        if (($currentCount + 1) > $maxDailyCount) {
            throw new RuntimeException(
                "Bridge transaction count would exceed daily limit of {$maxDailyCount}"
            );
        }
    }

    /**
     * Reconstruct a BridgeQuote value object from its cached array representation.
     *
     * @param array<string, mixed> $data
     */
    private function hydrateQuote(array $data): BridgeQuote
    {
        /** @var array<string, mixed> $routeData */
        $routeData = $data['route'];

        $route = new BridgeRoute(
            sourceChain: CrossChainNetwork::from((string) $routeData['source_chain']),
            destChain: CrossChainNetwork::from((string) $routeData['dest_chain']),
            token: (string) $routeData['token'],
            provider: BridgeProvider::from((string) $routeData['provider']),
            estimatedTimeSeconds: (int) $routeData['estimated_time_seconds'],
            baseFee: (string) $routeData['base_fee'],
        );

        return new BridgeQuote(
            quoteId: (string) $data['quote_id'],
            route: $route,
            inputAmount: (string) $data['input_amount'],
            outputAmount: (string) $data['output_amount'],
            fee: (string) $data['fee'],
            feeCurrency: (string) $data['fee_currency'],
            estimatedTimeSeconds: (int) $data['estimated_time_seconds'],
            expiresAt: CarbonImmutable::parse((string) $data['expires_at']),
        );
    }

    /**
     * Check the status of a bridge transaction.
     *
     * @return array{status: BridgeStatus, source_tx_hash: ?string, dest_tx_hash: ?string, confirmations: int}
     */
    public function checkStatus(
        string $transactionId,
        BridgeProvider $provider,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $adapter = $this->getAdapterForProvider($provider);
        $result = $adapter->getBridgeStatus($transactionId);

        if ($result['status'] === BridgeStatus::COMPLETED) {
            BridgeTransactionCompleted::dispatch(
                $transactionId,
                $sourceChain,
                $destChain,
                $token,
                $amount,
                $provider,
                $result['source_tx_hash'],
                $result['dest_tx_hash'],
            );
        }

        if ($result['status'] === BridgeStatus::FAILED) {
            BridgeTransactionFailed::dispatch(
                $transactionId,
                $sourceChain,
                $destChain,
                $token,
                $amount,
                $provider,
                'Bridge transaction failed',
            );
        }

        return $result;
    }

    /**
     * Get all supported routes across all adapters.
     *
     * @return array<BridgeRoute>
     */
    public function getAllSupportedRoutes(): array
    {
        $routes = [];

        foreach ($this->adapters as $adapter) {
            $routes = array_merge($routes, $adapter->getSupportedRoutes());
        }

        return $routes;
    }

    /**
     * Get supported chains (those with at least one bridge route).
     *
     * @return array<CrossChainNetwork>
     */
    public function getSupportedChains(): array
    {
        $chains = [];

        foreach ($this->getAllSupportedRoutes() as $route) {
            $chains[$route->sourceChain->value] = $route->sourceChain;
            $chains[$route->destChain->value] = $route->destChain;
        }

        return array_values($chains);
    }

    private function getAdapterForProvider(BridgeProvider $provider): BridgeAdapterInterface
    {
        if (! isset($this->adapters[$provider->value])) {
            throw new RuntimeException("No adapter registered for provider: {$provider->value}");
        }

        return $this->adapters[$provider->value];
    }
}
