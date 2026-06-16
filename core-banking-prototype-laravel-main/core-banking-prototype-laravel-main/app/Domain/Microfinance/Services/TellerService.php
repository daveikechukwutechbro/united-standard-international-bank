<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Models\TellerCashier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TellerService
{
    /**
     * Register a new teller / cashier.
     */
    public function registerTeller(int $userId, string $name, ?string $branch = null): TellerCashier
    {
        return TellerCashier::create([
            'user_id'       => $userId,
            'name'          => $name,
            'branch'        => $branch,
            'vault_balance' => '0.00',
            'currency'      => 'USD',
            'is_active'     => true,
        ]);
    }

    /**
     * Record cash received into the teller vault.
     *
     * @param numeric-string $amount
     *
     * @throws RuntimeException
     */
    public function recordCashIn(string $tellerId, string $amount): TellerCashier
    {
        return DB::transaction(function () use ($tellerId, $amount): TellerCashier {
            $teller = TellerCashier::lockForUpdate()->findOrFail($tellerId);

            /** @var numeric-string $vaultBalance */
            $vaultBalance = (string) $teller->vault_balance;
            $newBalance = bcadd($vaultBalance, $amount, 2);
            $teller->update(['vault_balance' => $newBalance]);

            return $teller->refresh();
        });
    }

    /**
     * Record cash paid out from the teller vault.
     *
     * @param numeric-string $amount
     *
     * @throws RuntimeException
     */
    public function recordCashOut(string $tellerId, string $amount): TellerCashier
    {
        return DB::transaction(function () use ($tellerId, $amount): TellerCashier {
            $teller = TellerCashier::lockForUpdate()->findOrFail($tellerId);

            /** @var numeric-string $vaultBalance */
            $vaultBalance = (string) $teller->vault_balance;
            if (bccomp($vaultBalance, $amount, 2) < 0) {
                throw new RuntimeException(
                    "Insufficient vault balance. Balance: {$teller->vault_balance}, " .
                    "requested: {$amount}."
                );
            }

            $newBalance = bcsub($vaultBalance, $amount, 2);
            $teller->update(['vault_balance' => $newBalance]);

            return $teller->refresh();
        });
    }

    /**
     * Reconcile the teller vault — records the reconciliation timestamp.
     *
     * @throws RuntimeException
     */
    public function reconcile(string $tellerId): TellerCashier
    {
        $teller = TellerCashier::find($tellerId);

        if ($teller === null) {
            throw new RuntimeException("Teller not found: {$tellerId}");
        }

        $teller->update(['last_reconciled_at' => Carbon::now()]);

        return $teller->fresh() ?? $teller;
    }

    /**
     * Get the current vault balance for a teller.
     *
     * @return array{balance: string, currency: string, last_reconciled: string|null}
     *
     * @throws RuntimeException
     */
    public function getVaultBalance(string $tellerId): array
    {
        $teller = TellerCashier::find($tellerId);

        if ($teller === null) {
            throw new RuntimeException("Teller not found: {$tellerId}");
        }

        return [
            'balance'         => number_format((float) $teller->vault_balance, 2, '.', ''),
            'currency'        => $teller->currency,
            'last_reconciled' => $teller->last_reconciled_at?->toIso8601String(),
        ];
    }

    /**
     * Deactivate a teller / cashier.
     *
     * @throws RuntimeException
     */
    public function deactivateTeller(string $tellerId): TellerCashier
    {
        $teller = TellerCashier::find($tellerId);

        if ($teller === null) {
            throw new RuntimeException("Teller not found: {$tellerId}");
        }

        $teller->update(['is_active' => false]);

        return $teller->fresh() ?? $teller;
    }
}
