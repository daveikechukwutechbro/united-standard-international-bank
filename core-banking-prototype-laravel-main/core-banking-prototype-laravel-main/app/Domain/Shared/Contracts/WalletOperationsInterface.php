<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Interface for wallet operations used by external domains.
 *
 * This interface enables domain decoupling by allowing domains like
 * Exchange, Stablecoin, Basket, AgentProtocol, etc. to depend on
 * an abstraction rather than the concrete Wallet domain implementation.
 *
 * All amounts are strings for precision (use bcmath for calculations).
 * All IDs are UUIDs as strings.
 *
 * @see \App\Domain\Wallet\Services\WalletService for implementation
 */
interface WalletOperationsInterface
{
    /**
     * Deposit funds into a wallet.
     *
     * @param string $walletId Wallet UUID
     * @param string $assetCode Currency/asset code (e.g., 'USD', 'BTC', 'ETH')
     * @param string $amount Amount to deposit (as string for precision)
     * @param string $reference Transaction reference/description
     * @param array<string, mixed> $metadata Additional transaction metadata
     * @return string Transaction ID
     */
    public function deposit(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string;

    /**
     * Withdraw funds from a wallet.
     *
     * @param string $walletId Wallet UUID
     * @param string $assetCode Currency/asset code
     * @param string $amount Amount to withdraw (as string for precision)
     * @param string $reference Transaction reference/description
     * @param array<string, mixed> $metadata Additional transaction metadata
     * @return string Transaction ID
     *
     * @throws \App\Domain\Wallet\Exceptions\InsufficientBalanceException When balance is insufficient
     */
    public function withdraw(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string;

    /**
     * Get the balance for a specific asset in a wallet.
     *
     * @param string $walletId Wallet UUID
     * @param string $assetCode Currency/asset code
     * @return string Balance as string for precision
     */
    public function getBalance(string $walletId, string $assetCode): string;

    /**
     * Get all balances for a wallet.
     *
     * @param string $walletId Wallet UUID
     * @return array<string, string> Map of asset code => balance
     */
    public function getAllBalances(string $walletId): array;

    /**
     * Check if wallet has sufficient balance for an operation.
     *
     * @param string $walletId Wallet UUID
     * @param string $assetCode Currency/asset code
     * @param string $amount Amount to check (as string for precision)
     * @return bool True if balance >= amount
     */
    public function hasSufficientBalance(string $walletId, string $assetCode, string $amount): bool;

    /**
     * Lock (reserve) funds in a wallet for pending operations.
     *
     * Used for escrow, pending trades, and other operations that
     * require funds to be held but not yet transferred.
     *
     * @param string $walletId Wallet UUID
     * @param string $assetCode Currency/asset code
     * @param string $amount Amount to lock (as string for precision)
     * @param string $reason Reason for the lock (e.g., 'escrow', 'pending_trade')
     * @param array<string, mixed> $metadata Additional lock metadata
     * @return string Lock ID (use to unlock or execute later)
     *
     * @throws \App\Domain\Wallet\Exceptions\InsufficientBalanceException When balance is insufficient
     */
    public function lockFunds(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reason,
        array $metadata = []
    ): string;

    /**
     * Unlock previously locked funds (release back to available balance).
     *
     * @param string $lockId The lock ID returned from lockFunds()
     * @return bool True if unlocked successfully
     *
     * @throws \App\Domain\Wallet\Exceptions\LockNotFoundException When lock ID is not found
     */
    public function unlockFunds(string $lockId): bool;

    /**
     * Execute a lock by transferring the locked funds to another wallet.
     *
     * This atomically releases the lock and transfers the funds.
     *
     * @param string $lockId The lock ID returned from lockFunds()
     * @param string $destinationWalletId Destination wallet UUID
     * @param string $reference Transaction reference
     * @return string Transaction ID
     *
     * @throws \App\Domain\Wallet\Exceptions\LockNotFoundException When lock ID is not found
     */
    public function executeLock(string $lockId, string $destinationWalletId, string $reference = ''): string;

    /**
     * Transfer funds between two wallets.
     *
     * @param string $fromWalletId Source wallet UUID
     * @param string $toWalletId Destination wallet UUID
     * @param string $assetCode Currency/asset code
     * @param string $amount Amount to transfer (as string for precision)
     * @param string $reference Transaction reference/description
     * @param array<string, mixed> $metadata Additional transaction metadata
     * @return string Transaction ID
     *
     * @throws \App\Domain\Wallet\Exceptions\InsufficientBalanceException When source balance is insufficient
     */
    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string;

    /**
     * Convert between different assets within the same wallet.
     *
     * @param string $walletId Wallet UUID
     * @param string $fromAssetCode Source asset code
     * @param string $toAssetCode Destination asset code
     * @param string $amount Amount of source asset to convert
     * @param string|null $exchangeRate Exchange rate (if null, fetches current rate)
     * @return array{
     *     transaction_id: string,
     *     from_amount: string,
     *     to_amount: string,
     *     rate_used: string
     * } Conversion result
     *
     * @throws \App\Domain\Wallet\Exceptions\InsufficientBalanceException When balance is insufficient
     * @throws \App\Domain\Wallet\Exceptions\UnsupportedConversionException When conversion pair is not supported
     */
    public function convert(
        string $walletId,
        string $fromAssetCode,
        string $toAssetCode,
        string $amount,
        ?string $exchangeRate = null
    ): array;

    /**
     * Get wallet details by UUID.
     *
     * @param string $walletId Wallet UUID
     * @return array{
     *     id: string,
     *     uuid: string,
     *     owner_id: string,
     *     owner_type: string,
     *     type: string,
     *     status: string,
     *     created_at: string
     * }|null Wallet details or null if not found
     */
    public function getWallet(string $walletId): ?array;

    /**
     * Check if a wallet exists.
     *
     * @param string $walletId Wallet UUID
     * @return bool True if wallet exists
     */
    public function walletExists(string $walletId): bool;
}
