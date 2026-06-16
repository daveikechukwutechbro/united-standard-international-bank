<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Services;

use App\Domain\Account\Models\Account;
use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Shared\Contracts\WalletOperationsInterface;
use App\Domain\Shared\Logging\AuditLogger;
use App\Domain\Shared\Validation\FinancialInputValidator;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\LockNotFoundException;
use App\Domain\Wallet\Exceptions\UnsupportedConversionException;
use App\Domain\Wallet\Workflows\WalletConvertWorkflow;
use App\Domain\Wallet\Workflows\WalletDepositWorkflow;
use App\Domain\Wallet\Workflows\WalletTransferWorkflow;
use App\Domain\Wallet\Workflows\WalletWithdrawWorkflow;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

class WalletOperationsService implements WalletOperationsInterface
{
    use FinancialInputValidator;
    use AuditLogger;

    private const LOCK_PREFIX = 'wallet_lock:';

    private const LOCK_TTL = 3600; // 1 hour (reduced from 24h for security)

    public function __construct(
        private readonly ExchangeRateService $exchangeRateService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function deposit(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        // Input validation
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($assetCode);
        $this->validatePositiveAmount($amount);
        $this->validateReference($reference);
        $this->validateMetadata($metadata);

        $this->auditOperationStart('wallet_deposit', [
            'wallet_id'  => $walletId,
            'asset_code' => $assetCode,
            'amount'     => $amount,
        ]);

        $transactionId = (string) Str::uuid();

        try {
            $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
            $workflow->start(
                __account_uuid($walletId),
                $assetCode,
                $amount
            );

            $this->auditOperationSuccess('wallet_deposit', [
                'transaction_id' => $transactionId,
                'wallet_id'      => $walletId,
                'amount'         => $amount,
            ]);

            return $transactionId;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_deposit', $e->getMessage(), [
                'wallet_id' => $walletId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function withdraw(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        // Input validation
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($assetCode);
        $this->validatePositiveAmount($amount);
        $this->validateReference($reference);
        $this->validateMetadata($metadata);

        $this->auditOperationStart('wallet_withdraw', [
            'wallet_id'  => $walletId,
            'asset_code' => $assetCode,
            'amount'     => $amount,
        ]);

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            $this->auditOperationFailure('wallet_withdraw', 'Account not found', [
                'wallet_id' => $walletId,
            ]);
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            $this->auditOperationFailure('wallet_withdraw', 'Insufficient balance', [
                'wallet_id' => $walletId,
                'required'  => $amount,
                'available' => $availableBalance,
            ]);
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, $availableBalance);
        }

        $transactionId = (string) Str::uuid();

        try {
            $workflow = WorkflowStub::make(WalletWithdrawWorkflow::class);
            $workflow->start(
                __account_uuid($walletId),
                $assetCode,
                $amount
            );

            $this->auditOperationSuccess('wallet_withdraw', [
                'transaction_id' => $transactionId,
                'wallet_id'      => $walletId,
                'amount'         => $amount,
            ]);

            return $transactionId;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_withdraw', $e->getMessage(), [
                'wallet_id' => $walletId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(string $walletId, string $assetCode): string
    {
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($assetCode);

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return '0';
        }

        return (string) $account->getBalance($assetCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllBalances(string $walletId): array
    {
        $this->validateUuid($walletId, 'wallet ID');

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return [];
        }

        $balances = [];
        foreach ($account->balances ?? [] as $balance) {
            $balances[$balance->asset_code] = (string) $balance->amount;
        }

        return $balances;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSufficientBalance(string $walletId, string $assetCode, string $amount): bool
    {
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($assetCode);
        $this->validateNonNegativeAmount($amount);

        $balance = $this->getBalance($walletId, $assetCode);

        return $this->compareAmounts($balance, $amount) >= 0;
    }

    /**
     * {@inheritDoc}
     */
    public function lockFunds(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reason,
        array $metadata = []
    ): string {
        // Input validation
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($assetCode);
        $this->validatePositiveAmount($amount);
        $this->validateReference($reason, 'reason');
        $this->validateMetadata($metadata);

        $this->auditOperationStart('wallet_lock_funds', [
            'wallet_id'  => $walletId,
            'asset_code' => $assetCode,
            'amount'     => $amount,
            'reason'     => $reason,
        ]);

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            $this->auditOperationFailure('wallet_lock_funds', 'Account not found', [
                'wallet_id' => $walletId,
            ]);
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            $this->auditOperationFailure('wallet_lock_funds', 'Insufficient balance', [
                'wallet_id' => $walletId,
                'required'  => $amount,
                'available' => $availableBalance,
            ]);
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, $availableBalance);
        }

        $lockId = 'lock_' . Str::uuid();

        // Store lock in cache with encryption for sensitive data
        $lockData = [
            'wallet_id'  => $walletId,
            'asset_code' => $assetCode,
            'amount'     => $amount,
            'reason'     => $reason,
            'metadata'   => $metadata,
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(self::LOCK_TTL)->toIso8601String(),
        ];

        // Encrypt the lock data before storing
        $encryptedData = encrypt($lockData);
        Cache::put(self::LOCK_PREFIX . $lockId, $encryptedData, self::LOCK_TTL);

        // Deduct from available balance (via withdrawal workflow)
        try {
            $workflow = WorkflowStub::make(WalletWithdrawWorkflow::class);
            $workflow->start(
                __account_uuid($walletId),
                $assetCode,
                $amount
            );

            $this->auditOperationSuccess('wallet_lock_funds', [
                'lock_id'   => $lockId,
                'wallet_id' => $walletId,
                'amount'    => $amount,
            ]);

            return $lockId;
        } catch (Exception $e) {
            // Clean up the lock if withdrawal fails
            Cache::forget(self::LOCK_PREFIX . $lockId);
            $this->auditOperationFailure('wallet_lock_funds', $e->getMessage(), [
                'wallet_id' => $walletId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unlockFunds(string $lockId): bool
    {
        $this->validateLockId($lockId);

        $this->auditOperationStart('wallet_unlock_funds', ['lock_id' => $lockId]);

        $encryptedData = Cache::get(self::LOCK_PREFIX . $lockId);

        if (! $encryptedData) {
            $this->auditOperationFailure('wallet_unlock_funds', 'Lock not found', [
                'lock_id' => $lockId,
            ]);
            throw new LockNotFoundException($lockId);
        }

        // Decrypt the lock data
        $lockData = decrypt($encryptedData);

        try {
            // Credit back the locked amount
            $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
            $workflow->start(
                __account_uuid($lockData['wallet_id']),
                $lockData['asset_code'],
                $lockData['amount']
            );

            Cache::forget(self::LOCK_PREFIX . $lockId);

            $this->auditOperationSuccess('wallet_unlock_funds', [
                'lock_id'   => $lockId,
                'wallet_id' => $lockData['wallet_id'],
                'amount'    => $lockData['amount'],
            ]);

            return true;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_unlock_funds', $e->getMessage(), [
                'lock_id' => $lockId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function executeLock(string $lockId, string $destinationWalletId, string $reference = ''): string
    {
        $this->validateLockId($lockId);
        $this->validateUuid($destinationWalletId, 'destination wallet ID');
        $this->validateReference($reference);

        $this->auditOperationStart('wallet_execute_lock', [
            'lock_id'               => $lockId,
            'destination_wallet_id' => $destinationWalletId,
        ]);

        $encryptedData = Cache::get(self::LOCK_PREFIX . $lockId);

        if (! $encryptedData) {
            $this->auditOperationFailure('wallet_execute_lock', 'Lock not found', [
                'lock_id' => $lockId,
            ]);
            throw new LockNotFoundException($lockId);
        }

        // Decrypt the lock data
        $lockData = decrypt($encryptedData);

        $transactionId = (string) Str::uuid();

        try {
            // Credit the destination wallet
            $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
            $workflow->start(
                __account_uuid($destinationWalletId),
                $lockData['asset_code'],
                $lockData['amount']
            );

            // Remove the lock
            Cache::forget(self::LOCK_PREFIX . $lockId);

            $this->auditOperationSuccess('wallet_execute_lock', [
                'transaction_id'        => $transactionId,
                'lock_id'               => $lockId,
                'destination_wallet_id' => $destinationWalletId,
                'amount'                => $lockData['amount'],
            ]);

            return $transactionId;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_execute_lock', $e->getMessage(), [
                'lock_id' => $lockId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        // Input validation
        $this->validateUuid($fromWalletId, 'source wallet ID');
        $this->validateUuid($toWalletId, 'destination wallet ID');
        $this->validateAssetCode($assetCode);
        $this->validatePositiveAmount($amount);
        $this->validateReference($reference);
        $this->validateMetadata($metadata);

        $this->auditOperationStart('wallet_transfer', [
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id'   => $toWalletId,
            'asset_code'     => $assetCode,
            'amount'         => $amount,
        ]);

        $account = Account::where('uuid', $fromWalletId)->first();

        if (! $account) {
            $this->auditOperationFailure('wallet_transfer', 'Source account not found', [
                'from_wallet_id' => $fromWalletId,
            ]);
            throw new InsufficientBalanceException($fromWalletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            $this->auditOperationFailure('wallet_transfer', 'Insufficient balance', [
                'from_wallet_id' => $fromWalletId,
                'required'       => $amount,
                'available'      => $availableBalance,
            ]);
            throw new InsufficientBalanceException($fromWalletId, $assetCode, $amount, $availableBalance);
        }

        $transactionId = (string) Str::uuid();

        try {
            $workflow = WorkflowStub::make(WalletTransferWorkflow::class);
            $workflow->start(
                __account_uuid($fromWalletId),
                __account_uuid($toWalletId),
                $assetCode,
                $amount,
                $reference
            );

            $this->auditOperationSuccess('wallet_transfer', [
                'transaction_id' => $transactionId,
                'from_wallet_id' => $fromWalletId,
                'to_wallet_id'   => $toWalletId,
                'amount'         => $amount,
            ]);

            return $transactionId;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_transfer', $e->getMessage(), [
                'from_wallet_id' => $fromWalletId,
                'to_wallet_id'   => $toWalletId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function convert(
        string $walletId,
        string $fromAssetCode,
        string $toAssetCode,
        string $amount,
        ?string $exchangeRate = null
    ): array {
        // Input validation
        $this->validateUuid($walletId, 'wallet ID');
        $this->validateAssetCode($fromAssetCode, 'source asset code');
        $this->validateAssetCode($toAssetCode, 'target asset code');
        $this->validatePositiveAmount($amount);

        if ($exchangeRate !== null) {
            $this->validateExchangeRate($exchangeRate);
        }

        $this->auditOperationStart('wallet_convert', [
            'wallet_id'       => $walletId,
            'from_asset_code' => $fromAssetCode,
            'to_asset_code'   => $toAssetCode,
            'amount'          => $amount,
        ]);

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            $this->auditOperationFailure('wallet_convert', 'Account not found', [
                'wallet_id' => $walletId,
            ]);
            throw new InsufficientBalanceException($walletId, $fromAssetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($fromAssetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            $this->auditOperationFailure('wallet_convert', 'Insufficient balance', [
                'wallet_id' => $walletId,
                'required'  => $amount,
                'available' => $availableBalance,
            ]);
            throw new InsufficientBalanceException($walletId, $fromAssetCode, $amount, $availableBalance);
        }

        // Get exchange rate if not provided
        if ($exchangeRate === null) {
            $exchangeRate = $this->getExchangeRateFromService($fromAssetCode, $toAssetCode);
            if ($exchangeRate === null) {
                $this->auditOperationFailure('wallet_convert', 'Unsupported conversion', [
                    'from_asset_code' => $fromAssetCode,
                    'to_asset_code'   => $toAssetCode,
                ]);
                throw new UnsupportedConversionException($fromAssetCode, $toAssetCode);
            }
        }

        $transactionId = (string) Str::uuid();
        $toAmount = $this->multiplyAmounts($amount, $exchangeRate);

        try {
            $workflow = WorkflowStub::make(WalletConvertWorkflow::class);
            $workflow->start(
                __account_uuid($walletId),
                $fromAssetCode,
                $toAssetCode,
                $amount
            );

            $result = [
                'transaction_id' => $transactionId,
                'from_amount'    => $amount,
                'to_amount'      => $toAmount,
                'rate_used'      => $exchangeRate,
            ];

            $this->auditOperationSuccess('wallet_convert', $result);

            return $result;
        } catch (Exception $e) {
            $this->auditOperationFailure('wallet_convert', $e->getMessage(), [
                'wallet_id' => $walletId,
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getWallet(string $walletId): ?array
    {
        $this->validateUuid($walletId, 'wallet ID');

        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return null;
        }

        return [
            'id'         => (string) $account->id,
            'uuid'       => $account->uuid,
            'owner_id'   => (string) $account->user_id,
            'owner_type' => 'user',
            'type'       => $account->type ?? 'standard',
            'status'     => $account->status ?? 'active',
            'created_at' => $account->created_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function walletExists(string $walletId): bool
    {
        $this->validateUuid($walletId, 'wallet ID');

        return Account::where('uuid', $walletId)->exists();
    }

    /**
     * Get exchange rate between two assets via injected service.
     */
    private function getExchangeRateFromService(string $fromAssetCode, string $toAssetCode): ?string
    {
        try {
            return (string) $this->exchangeRateService->getRate($fromAssetCode, $toAssetCode);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Compare two amounts with bccomp.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount
     * @return int -1, 0, or 1 as per bccomp
     */
    private function compareAmounts(string $amount1, string $amount2): int
    {
        /** @var numeric-string $a */
        $a = $amount1;
        /** @var numeric-string $b */
        $b = $amount2;

        return bccomp($a, $b, 8);
    }

    /**
     * Multiply two amounts with bcmul.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount (typically exchange rate)
     * @return string Result
     */
    private function multiplyAmounts(string $amount1, string $amount2): string
    {
        /** @var numeric-string $a */
        $a = $amount1;
        /** @var numeric-string $b */
        $b = $amount2;

        return bcmul($a, $b, 8);
    }
}
