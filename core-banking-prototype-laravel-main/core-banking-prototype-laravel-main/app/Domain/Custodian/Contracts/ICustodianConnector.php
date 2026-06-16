<?php

declare(strict_types=1);

namespace App\Domain\Custodian\Contracts;

use App\Domain\Account\DataObjects\Money;
use App\Domain\Custodian\ValueObjects\AccountInfo;
use App\Domain\Custodian\ValueObjects\TransactionReceipt;
use App\Domain\Custodian\ValueObjects\TransferRequest;

interface ICustodianConnector
{
    /**
     * Get the custodian name/identifier.
     */
    public function getName(): string;

    /**
     * Check if the custodian is available.
     */
    public function isAvailable(): bool;

    /**
     * Get account balance for a specific asset.
     */
    public function getBalance(string $accountId, string $assetCode): Money;

    /**
     * Get full account information.
     */
    public function getAccountInfo(string $accountId): AccountInfo;

    /**
     * Initiate a transfer between accounts.
     */
    public function initiateTransfer(TransferRequest $request): TransactionReceipt;

    /**
     * Get transaction status by ID.
     */
    public function getTransactionStatus(string $transactionId): TransactionReceipt;

    /**
     * Cancel a pending transaction.
     */
    public function cancelTransaction(string $transactionId): bool;

    /**
     * Get supported asset codes.
     */
    public function getSupportedAssets(): array;

    /**
     * Validate account exists and is active.
     */
    public function validateAccount(string $accountId): bool;

    /**
     * Get transaction history.
     */
    public function getTransactionHistory(string $accountId, ?int $limit = 100, ?int $offset = 0): array;
}
