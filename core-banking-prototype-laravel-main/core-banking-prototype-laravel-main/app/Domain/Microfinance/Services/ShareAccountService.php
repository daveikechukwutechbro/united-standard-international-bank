<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Enums\ShareAccountStatus;
use App\Domain\Microfinance\Models\ShareAccount;
use Illuminate\Support\Str;
use RuntimeException;

class ShareAccountService
{
    /**
     * Open a new cooperative share account.
     */
    public function openAccount(
        int $userId,
        ?string $groupId = null,
        string $currency = 'USD',
    ): ShareAccount {
        $nominalValue = (float) config('microfinance.share_accounts.nominal_value', 100.00);
        $accountNumber = 'SHA-' . strtoupper(Str::random(8));

        return ShareAccount::create([
            'user_id'          => $userId,
            'group_id'         => $groupId,
            'account_number'   => $accountNumber,
            'shares_purchased' => 0,
            'nominal_value'    => number_format($nominalValue, 2, '.', ''),
            'total_value'      => '0.00',
            'status'           => ShareAccountStatus::ACTIVE,
            'currency'         => $currency,
            'dividend_balance' => '0.00',
        ]);
    }

    /**
     * Purchase shares for an account.
     *
     * @throws RuntimeException
     */
    public function purchaseShares(string $accountId, int $shares): ShareAccount
    {
        $account = ShareAccount::find($accountId);

        if ($account === null) {
            throw new RuntimeException("Share account not found: {$accountId}");
        }

        $maxShares = (int) config('microfinance.share_accounts.max_shares', 1000);
        $newTotal = $account->shares_purchased + $shares;

        if ($newTotal > $maxShares) {
            throw new RuntimeException(
                "Cannot purchase {$shares} shares. Would exceed maximum of {$maxShares} shares. " .
                "Current: {$account->shares_purchased}."
            );
        }

        /** @var numeric-string $nominalVal */
        $nominalVal = sprintf('%.10f', (float) $account->nominal_value);
        $newTotalValue = bcmul((string) $newTotal, $nominalVal, 2);

        $account->update([
            'shares_purchased' => $newTotal,
            'total_value'      => $newTotalValue,
        ]);

        return $account->fresh() ?? $account;
    }

    /**
     * Redeem (sell back) shares from an account.
     *
     * @throws RuntimeException
     */
    public function redeemShares(string $accountId, int $shares): ShareAccount
    {
        $account = ShareAccount::find($accountId);

        if ($account === null) {
            throw new RuntimeException("Share account not found: {$accountId}");
        }

        if ($account->shares_purchased < $shares) {
            throw new RuntimeException(
                "Insufficient shares. Account has {$account->shares_purchased}, " .
                "requested redemption of {$shares}."
            );
        }

        $newTotal = $account->shares_purchased - $shares;
        /** @var numeric-string $nominalVal */
        $nominalVal = sprintf('%.10f', (float) $account->nominal_value);
        $newTotalValue = bcmul((string) $newTotal, $nominalVal, 2);

        $account->update([
            'shares_purchased' => $newTotal,
            'total_value'      => $newTotalValue,
        ]);

        return $account->fresh() ?? $account;
    }

    /**
     * Calculate dividend for an account without modifying it.
     *
     * @return array{shares: int, dividend_per_share: float, total_dividend: float}
     *
     * @throws RuntimeException
     */
    public function calculateDividend(string $accountId, float $dividendPerShare): array
    {
        $account = ShareAccount::find($accountId);

        if ($account === null) {
            throw new RuntimeException("Share account not found: {$accountId}");
        }

        $totalDividend = $account->shares_purchased * $dividendPerShare;

        return [
            'shares'             => $account->shares_purchased,
            'dividend_per_share' => $dividendPerShare,
            'total_dividend'     => $totalDividend,
        ];
    }

    /**
     * Distribute dividend to an account's dividend balance.
     *
     * @throws RuntimeException
     */
    public function distributeDividend(string $accountId, float $dividendPerShare): ShareAccount
    {
        $account = ShareAccount::find($accountId);

        if ($account === null) {
            throw new RuntimeException("Share account not found: {$accountId}");
        }

        /** @var numeric-string $divPerShare */
        $divPerShare = sprintf('%.8f', $dividendPerShare);
        $dividendAmount = bcmul((string) $account->shares_purchased, $divPerShare, 2);

        /** @var numeric-string $currentDividend */
        $currentDividend = sprintf('%.10f', (float) $account->dividend_balance);
        $newDividendBalance = bcadd($currentDividend, $dividendAmount, 2);

        $account->update(['dividend_balance' => $newDividendBalance]);

        return $account->fresh() ?? $account;
    }

    /**
     * Close a share account.
     *
     * @throws RuntimeException
     */
    public function closeAccount(string $accountId): ShareAccount
    {
        $account = ShareAccount::find($accountId);

        if ($account === null) {
            throw new RuntimeException("Share account not found: {$accountId}");
        }

        $account->update(['status' => ShareAccountStatus::CLOSED]);

        return $account->fresh() ?? $account;
    }
}
