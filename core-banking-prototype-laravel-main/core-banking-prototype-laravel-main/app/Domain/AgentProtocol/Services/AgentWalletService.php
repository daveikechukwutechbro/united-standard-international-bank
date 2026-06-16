<?php

declare(strict_types=1);

namespace App\Domain\AgentProtocol\Services;

use App\Domain\AgentProtocol\Aggregates\AgentTransactionAggregate;
use App\Domain\AgentProtocol\Aggregates\AgentWalletAggregate;
use App\Domain\AgentProtocol\Contracts\WalletOperationInterface;
use App\Domain\AgentProtocol\Models\AgentTransaction;
use App\Domain\AgentProtocol\Models\AgentWallet;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service for managing agent wallets with multi-currency support.
 *
 * Configuration is loaded from config/agent_protocol.php:
 * - wallet.supported_currencies: List of supported currency codes
 * - wallet.exchange_rate_cache_ttl: Cache duration for exchange rates
 * - wallet.transaction_fees: Fee rates by transaction type
 * - wallet.crypto_currencies: List of cryptocurrency codes
 */
class AgentWalletService implements WalletOperationInterface
{
    /**
     * Get supported currencies from configuration.
     *
     * @return array<string>
     */
    private function getSupportedCurrencies(): array
    {
        return config('agent_protocol.wallet.supported_currencies', ['USD', 'EUR', 'GBP']);
    }

    /**
     * Get exchange rate cache TTL from configuration.
     */
    private function getExchangeRateCacheTtl(): int
    {
        return (int) config('agent_protocol.wallet.exchange_rate_cache_ttl', 300);
    }

    /**
     * Get transaction fee rates from configuration.
     *
     * @return array<string, float>
     */
    private function getTransactionFeeRates(): array
    {
        return config('agent_protocol.wallet.transaction_fees', [
            'domestic'      => 0.01,
            'international' => 0.025,
            'crypto'        => 0.005,
            'escrow'        => 0.02,
        ]);
    }

    /**
     * Get crypto currencies from configuration.
     *
     * @return array<string>
     */
    private function getCryptoCurrencies(): array
    {
        return config('agent_protocol.wallet.crypto_currencies', ['BTC', 'ETH', 'USDT']);
    }

    /**
     * Create a new wallet for an agent.
     *
     * @param string $agentId The agent's DID
     * @param string $currency The wallet currency (default: USD)
     * @param float $initialBalance Initial balance to set
     * @param array<string, mixed> $metadata Additional wallet metadata
     * @return AgentWallet The created wallet
     * @throws InvalidArgumentException If currency is not supported
     */
    public function createWallet(
        string $agentId,
        string $currency = 'USD',
        float $initialBalance = 0.0,
        array $metadata = []
    ): AgentWallet {
        $supportedCurrencies = $this->getSupportedCurrencies();
        if (! in_array(strtoupper($currency), $supportedCurrencies, true)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}. Supported: " . implode(', ', $supportedCurrencies));
        }

        $walletId = 'wallet_' . Str::uuid()->toString();

        // Create wallet aggregate
        $aggregate = AgentWalletAggregate::create(
            walletId: $walletId,
            agentId: $agentId,
            currency: $currency,
            initialBalance: $initialBalance,
            metadata: $metadata
        );
        $aggregate->persist();

        // Create read model
        return AgentWallet::create([
            'wallet_id'         => $walletId,
            'agent_id'          => $agentId,
            'currency'          => $currency,
            'available_balance' => $initialBalance,
            'held_balance'      => 0.0,
            'total_balance'     => $initialBalance,
            'is_active'         => true,
            'metadata'          => $metadata,
        ]);
    }

    /**
     * Get the current balance for a wallet.
     *
     * {@inheritDoc}
     */
    public function getBalance(string $walletId, ?string $currency = null): array
    {
        $wallet = AgentWallet::where('wallet_id', $walletId)->firstOrFail();

        // Build balances array (per currency)
        $balances = [$wallet->currency => $wallet->total_balance];
        $available = [$wallet->currency => $wallet->available_balance];
        $held = [$wallet->currency => $wallet->held_balance];

        // Convert to target currency if requested
        if ($currency && $currency !== $wallet->currency) {
            $rate = $this->getExchangeRate($wallet->currency, $currency);
            $balances[$currency] = round($wallet->total_balance * $rate, 2);
            $available[$currency] = round($wallet->available_balance * $rate, 2);
            $held[$currency] = round($wallet->held_balance * $rate, 2);
        }

        return [
            'wallet_id'  => $walletId,
            'balances'   => $balances,
            'available'  => $available,
            'held'       => $held,
            'updated_at' => $wallet->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    /**
     * Transfer funds between wallets.
     *
     * {@inheritDoc}
     */
    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        float $amount,
        string $currency,
        array $metadata = []
    ): array {
        DB::beginTransaction();

        try {
            $fromWallet = AgentWallet::where('wallet_id', $fromWalletId)
                ->lockForUpdate()
                ->firstOrFail();
            $toWallet = AgentWallet::where('wallet_id', $toWalletId)
                ->lockForUpdate()
                ->firstOrFail();

            // Handle currency conversion
            $fromAmount = $amount;
            $toAmount = $amount;

            if ($fromWallet->currency !== $currency) {
                $rate = $this->getExchangeRate($currency, $fromWallet->currency);
                $fromAmount = round($amount * $rate, 2);
            }

            if ($toWallet->currency !== $currency) {
                $rate = $this->getExchangeRate($currency, $toWallet->currency);
                $toAmount = round($amount * $rate, 2);
            }

            // Check sufficient balance
            if ($fromWallet->available_balance < $fromAmount) {
                throw new InvalidArgumentException('Insufficient balance for transfer');
            }

            // Calculate fees using config-based rates
            $feeType = $this->determineFeeType($fromWallet->currency, $toWallet->currency);
            $feeRates = $this->getTransactionFeeRates();
            $feeAmount = round($amount * ($feeRates[$feeType] ?? 0.01), 2);

            // Create transaction aggregate
            $transactionId = 'trans_' . Str::uuid()->toString();
            $type = $metadata['type'] ?? 'transfer';
            $aggregate = AgentTransactionAggregate::initiate(
                transactionId: $transactionId,
                fromAgentId: $fromWallet->agent_id,
                toAgentId: $toWallet->agent_id,
                amount: $amount,
                currency: $currency,
                type: 'direct',
                metadata: $metadata
            );

            $aggregate->validate()
                ->calculateFees($feeAmount, $feeType)
                ->complete('success');
            $aggregate->persist();

            // Update wallet balances
            $this->updateWalletBalance($fromWalletId, -$fromAmount - $feeAmount);
            $this->updateWalletBalance($toWalletId, $toAmount);

            // Create transaction record
            AgentTransaction::create([
                'transaction_id' => $transactionId,
                'from_agent_id'  => $fromWallet->agent_id,
                'to_agent_id'    => $toWallet->agent_id,
                'amount'         => $amount,
                'currency'       => $currency,
                'fee_amount'     => $feeAmount,
                'fee_type'       => $feeType,
                'status'         => 'completed',
                'type'           => $type,
                'metadata'       => array_merge($metadata, [
                    'from_currency' => $fromWallet->currency,
                    'to_currency'   => $toWallet->currency,
                    'from_amount'   => $fromAmount,
                    'to_amount'     => $toAmount,
                ]),
            ]);

            DB::commit();

            Log::info('Agent wallet transfer completed', [
                'transaction_id' => $transactionId,
                'from_wallet'    => $fromWalletId,
                'to_wallet'      => $toWalletId,
                'amount'         => $amount,
                'currency'       => $currency,
            ]);

            return [
                'success'        => true,
                'transaction_id' => $transactionId,
                'from_wallet'    => $fromWalletId,
                'to_wallet'      => $toWalletId,
                'amount'         => $amount,
                'currency'       => $currency,
                'fee'            => $feeAmount,
                'timestamp'      => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Agent wallet transfer failed', [
                'error'       => $e->getMessage(),
                'from_wallet' => $fromWalletId,
                'to_wallet'   => $toWalletId,
            ]);
            throw $e;
        }
    }

    /**
     * Hold funds in a wallet for escrow or pending transactions.
     *
     * {@inheritDoc}
     */
    public function holdFunds(
        string $walletId,
        float $amount,
        string $currency,
        string $reason,
        ?int $expiresInSeconds = null
    ): array {
        $wallet = AgentWallet::where('wallet_id', $walletId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($wallet->available_balance < $amount) {
            throw new InvalidArgumentException('Insufficient available balance to hold');
        }

        // Generate hold ID
        $holdId = 'hold_' . Str::uuid()->toString();

        $aggregate = AgentWalletAggregate::retrieve($walletId);
        $aggregate->holdFunds($amount, $reason, [
            'hold_id'    => $holdId,
            'currency'   => $currency,
            'expires_at' => $expiresInSeconds ? now()->addSeconds($expiresInSeconds)->toIso8601String() : null,
        ]);
        $aggregate->persist();

        // Update read model
        $wallet->update([
            'available_balance' => $wallet->available_balance - $amount,
            'held_balance'      => $wallet->held_balance + $amount,
        ]);

        // Store hold details in cache for release lookup
        $expiresAt = $expiresInSeconds ? now()->addSeconds($expiresInSeconds) : now()->addDays(30);
        Cache::put("wallet_hold:{$holdId}", [
            'wallet_id' => $walletId,
            'amount'    => $amount,
            'currency'  => $currency,
            'reason'    => $reason,
        ], $expiresAt);

        return [
            'success'    => true,
            'hold_id'    => $holdId,
            'wallet_id'  => $walletId,
            'amount'     => $amount,
            'currency'   => $currency,
            'reason'     => $reason,
            'expires_at' => $expiresInSeconds ? $expiresAt->toIso8601String() : null,
        ];
    }

    /**
     * Release previously held funds.
     *
     * {@inheritDoc}
     */
    public function releaseFunds(string $holdId, ?string $releaseToWalletId = null): array
    {
        // Retrieve hold details from cache
        $holdDetails = Cache::get("wallet_hold:{$holdId}");

        if (! $holdDetails) {
            throw new InvalidArgumentException("Hold not found: {$holdId}");
        }

        $walletId = $holdDetails['wallet_id'];
        $amount = $holdDetails['amount'];
        $currency = $holdDetails['currency'];

        $targetWalletId = $releaseToWalletId ?? $walletId;

        $wallet = AgentWallet::where('wallet_id', $walletId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($wallet->held_balance < $amount) {
            throw new InvalidArgumentException('Insufficient held balance to release');
        }

        $aggregate = AgentWalletAggregate::retrieve($walletId);
        $aggregate->releaseFunds($amount, 'hold_release', ['hold_id' => $holdId]);
        $aggregate->persist();

        // Update read model
        $wallet->update([
            'available_balance' => $wallet->available_balance + $amount,
            'held_balance'      => $wallet->held_balance - $amount,
        ]);

        // If releasing to a different wallet, transfer the funds
        if ($releaseToWalletId && $releaseToWalletId !== $walletId) {
            $this->transfer($walletId, $releaseToWalletId, $amount, $currency, [
                'type'    => 'hold_release',
                'hold_id' => $holdId,
            ]);
        }

        // Remove hold from cache
        Cache::forget("wallet_hold:{$holdId}");

        return [
            'success'         => true,
            'hold_id'         => $holdId,
            'released_amount' => $amount,
            'currency'        => $currency,
            'released_to'     => $targetWalletId,
        ];
    }

    /**
     * Check if a wallet has sufficient balance for an operation.
     *
     * {@inheritDoc}
     */
    public function hasSufficientBalance(
        string $walletId,
        float $amount,
        string $currency,
        bool $includeHeld = false
    ): bool {
        $wallet = AgentWallet::where('wallet_id', $walletId)->first();

        if (! $wallet) {
            return false;
        }

        // Convert amount if currencies don't match
        $requiredAmount = $amount;
        if ($wallet->currency !== $currency) {
            $rate = $this->getExchangeRate($currency, $wallet->currency);
            $requiredAmount = round($amount * $rate, 2);
        }

        $availableBalance = $includeHeld
            ? $wallet->total_balance
            : $wallet->available_balance;

        return $availableBalance >= $requiredAmount;
    }

    /**
     * Get list of supported currencies.
     *
     * @return array<string> List of currency codes
     */
    public function listSupportedCurrencies(): array
    {
        return $this->getSupportedCurrencies();
    }

    /**
     * Check if a currency is supported.
     *
     * @param string $currency Currency code to check
     * @return bool True if currency is supported
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies(), true);
    }

    /**
     * Get exchange rate between currencies.
     *
     * Note: This is a mock implementation. In production, integrate with
     * a real exchange rate API service.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return float Exchange rate (1.0 if same currency or rate not found)
     */
    private function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}";
        $cacheTtl = $this->getExchangeRateCacheTtl();

        $rate = Cache::remember($cacheKey, $cacheTtl, function () use ($fromCurrency, $toCurrency): float {
            // Mock exchange rates (in production, use real exchange rate API)
            $rates = [
                'USD' => ['EUR' => 0.85, 'GBP' => 0.73, 'JPY' => 110.0, 'CHF' => 0.92, 'CAD' => 1.25, 'AUD' => 1.35, 'NZD' => 1.45],
                'EUR' => ['USD' => 1.18, 'GBP' => 0.86, 'JPY' => 129.0, 'CHF' => 1.08, 'CAD' => 1.47, 'AUD' => 1.59, 'NZD' => 1.71],
                'GBP' => ['USD' => 1.37, 'EUR' => 1.16, 'JPY' => 150.0, 'CHF' => 1.26, 'CAD' => 1.71, 'AUD' => 1.85, 'NZD' => 1.99],
            ];

            $rate = $rates[$fromCurrency][$toCurrency] ?? null;

            if ($rate === null) {
                Log::warning('Exchange rate not found, using fallback', [
                    'from' => $fromCurrency,
                    'to'   => $toCurrency,
                ]);

                return 1.0;
            }

            return (float) $rate;
        });

        return (float) $rate;
    }

    /**
     * Determine fee type based on currencies.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return string Fee type (domestic, international, or crypto)
     */
    private function determineFeeType(string $fromCurrency, string $toCurrency): string
    {
        if ($fromCurrency === $toCurrency) {
            return 'domestic';
        }

        // Check if crypto using config-based list
        $cryptoCurrencies = $this->getCryptoCurrencies();
        if (in_array($fromCurrency, $cryptoCurrencies, true) || in_array($toCurrency, $cryptoCurrencies, true)) {
            return 'crypto';
        }

        return 'international';
    }

    /**
     * Update wallet balance.
     */
    private function updateWalletBalance(string $walletId, float $amount): void
    {
        $aggregate = AgentWalletAggregate::retrieve($walletId);

        if ($amount > 0) {
            $aggregate->receivePayment(
                transactionId: 'internal_' . Str::uuid()->toString(),
                fromAgentId: 'system',
                amount: $amount,
                metadata: ['type' => 'internal_transfer']
            );
        } else {
            // For negative amounts (withdrawals), we don't have a direct method
            // This would need to be handled through the transaction aggregate
        }

        $aggregate->persist();

        // Update read model
        $wallet = AgentWallet::where('wallet_id', $walletId)->first();
        if ($wallet) {
            $wallet->update([
                'available_balance' => $wallet->available_balance + $amount,
                'total_balance'     => $wallet->total_balance + $amount,
            ]);
        }
    }

    /**
     * Get transaction history for a wallet.
     */
    public function getTransactionHistory(string $walletId, int $limit = 50): array
    {
        $wallet = AgentWallet::where('wallet_id', $walletId)->firstOrFail();

        $transactions = AgentTransaction::where(function ($query) use ($wallet) {
                $query->where('from_agent_id', $wallet->agent_id)
                      ->orWhere('to_agent_id', $wallet->agent_id);
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) use ($wallet) {
            $isOutgoing = $transaction->from_agent_id === $wallet->agent_id;

            return [
                'transaction_id' => $transaction->transaction_id,
                'type'           => $isOutgoing ? 'outgoing' : 'incoming',
                'amount'         => $transaction->amount,
                'currency'       => $transaction->currency,
                'fee'            => $isOutgoing ? $transaction->fee_amount : 0,
                'status'         => $transaction->status,
                'created_at'     => $transaction->created_at,
                'metadata'       => $transaction->metadata,
            ];
        })->toArray();
    }
}
