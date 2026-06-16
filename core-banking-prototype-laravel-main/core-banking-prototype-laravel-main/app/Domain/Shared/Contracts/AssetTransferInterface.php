<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Interface for asset transfer operations used by external domains.
 *
 * This interface enables domain decoupling by allowing domains like
 * Exchange, Stablecoin, AI, AgentProtocol, etc. to depend on an
 * abstraction rather than the concrete Asset domain implementation.
 *
 * Supports both same-asset transfers and cross-asset conversions.
 * All amounts are strings for precision (use bcmath for calculations).
 *
 * @see \App\Domain\Asset\Aggregates\AssetTransferAggregate for implementation
 */
interface AssetTransferInterface
{
    /**
     * Initiate a same-asset transfer between accounts.
     *
     * @param string $fromAccountId Source account UUID
     * @param string $toAccountId Destination account UUID
     * @param string $assetCode Asset code (e.g., 'USD', 'BTC', 'GCU')
     * @param string $amount Amount to transfer (as string for precision)
     * @param string $reference Transaction reference/description
     * @param array<string, mixed> $metadata Additional transfer metadata
     * @return string Transfer ID (UUID)
     *
     * @throws \App\Domain\Asset\Exceptions\InsufficientAssetBalanceException When balance is insufficient
     * @throws \App\Domain\Asset\Exceptions\AssetNotFoundException When asset code is not found
     */
    public function transfer(
        string $fromAccountId,
        string $toAccountId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string;

    /**
     * Initiate a cross-asset conversion transfer.
     *
     * Converts from one asset to another during transfer.
     *
     * @param string $fromAccountId Source account UUID
     * @param string $toAccountId Destination account UUID
     * @param string $fromAssetCode Source asset code
     * @param string $toAssetCode Destination asset code
     * @param string $fromAmount Amount of source asset
     * @param string|null $exchangeRate Exchange rate (if null, fetches current rate)
     * @param string $reference Transaction reference
     * @param array<string, mixed> $metadata Additional transfer metadata
     * @return array{
     *     transfer_id: string,
     *     from_amount: string,
     *     to_amount: string,
     *     rate_used: string
     * } Transfer result with conversion details
     *
     * @throws \App\Domain\Asset\Exceptions\InsufficientAssetBalanceException When balance is insufficient
     * @throws \App\Domain\Asset\Exceptions\UnsupportedAssetConversionException When conversion is not supported
     */
    public function convertAndTransfer(
        string $fromAccountId,
        string $toAccountId,
        string $fromAssetCode,
        string $toAssetCode,
        string $fromAmount,
        ?string $exchangeRate = null,
        string $reference = '',
        array $metadata = []
    ): array;

    /**
     * Get asset details by code.
     *
     * @param string $assetCode Asset code
     * @return array{
     *     code: string,
     *     name: string,
     *     type: string,
     *     symbol: string,
     *     decimals: int,
     *     is_active: bool,
     *     metadata: array<string, mixed>
     * }|null Asset details or null if not found
     */
    public function getAssetDetails(string $assetCode): ?array;

    /**
     * Get all available assets.
     *
     * @param string|null $type Filter by type ('fiat', 'crypto', 'commodity', 'token')
     * @param bool $activeOnly Only return active assets
     * @return array<int, array{
     *     code: string,
     *     name: string,
     *     type: string,
     *     symbol: string,
     *     is_active: bool
     * }>
     */
    public function getAvailableAssets(?string $type = null, bool $activeOnly = true): array;

    /**
     * Check if an asset exists and is active.
     *
     * @param string $assetCode Asset code
     * @return bool True if asset exists and is active
     */
    public function assetExists(string $assetCode): bool;

    /**
     * Validate an asset operation before execution.
     *
     * @param string $assetCode Asset code
     * @param string $operation Operation type ('transfer', 'convert', 'deposit', 'withdraw')
     * @param array<string, mixed> $context Operation context (amounts, accounts, etc.)
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>
     * }
     */
    public function validateOperation(
        string $assetCode,
        string $operation,
        array $context = []
    ): array;

    /**
     * Get the current exchange rate between two assets.
     *
     * @param string $fromAssetCode Source asset code
     * @param string $toAssetCode Destination asset code
     * @return string|null Exchange rate as string, or null if not available
     */
    public function getExchangeRate(string $fromAssetCode, string $toAssetCode): ?string;

    /**
     * Calculate the converted amount between two assets.
     *
     * @param string $fromAssetCode Source asset code
     * @param string $toAssetCode Destination asset code
     * @param string $amount Amount to convert
     * @return array{
     *     converted_amount: string,
     *     rate_used: string,
     *     fee_amount: string,
     *     net_amount: string
     * }|null Conversion calculation or null if not supported
     */
    public function calculateConversion(
        string $fromAssetCode,
        string $toAssetCode,
        string $amount
    ): ?array;

    /**
     * Get transfer status by ID.
     *
     * @param string $transferId Transfer UUID
     * @return array{
     *     id: string,
     *     status: string,
     *     from_account_id: string,
     *     to_account_id: string,
     *     from_asset_code: string,
     *     to_asset_code: string,
     *     from_amount: string,
     *     to_amount: string,
     *     exchange_rate: string|null,
     *     created_at: string,
     *     completed_at: string|null,
     *     failure_reason: string|null
     * }|null Transfer details or null if not found
     */
    public function getTransferStatus(string $transferId): ?array;

    /**
     * Check if a conversion between two assets is supported.
     *
     * @param string $fromAssetCode Source asset code
     * @param string $toAssetCode Destination asset code
     * @return bool True if conversion is supported
     */
    public function isConversionSupported(string $fromAssetCode, string $toAssetCode): bool;

    /**
     * Format an amount according to asset precision.
     *
     * @param string $assetCode Asset code
     * @param string $amount Raw amount
     * @return string Formatted amount with proper decimals
     */
    public function formatAmount(string $assetCode, string $amount): string;
}
