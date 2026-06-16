<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services\Drivers;

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Enums\EntryStatus;
use App\Domain\Ledger\Models\JournalEntry;
use App\Domain\Ledger\Models\JournalLine;
use App\Domain\Ledger\Models\LedgerAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentDriver implements LedgerDriverInterface
{
    public function post(JournalEntry $entry): void
    {
        // EloquentDriver is a no-op for post() since LedgerService
        // already persists via Eloquent. This hook exists for drivers
        // that need to sync to an external system (e.g., TigerBeetle).
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function balance(string $accountCode, ?Carbon $asOf = null): array
    {
        $query = JournalLine::where('account_code', $accountCode)
            ->whereHas('entry', function (Builder $q): void {
                $q->where('status', EntryStatus::POSTED->value);
            });

        if ($asOf !== null) {
            $query->whereHas('entry', function (Builder $q) use ($asOf): void {
                $q->where('posted_at', '<=', $asOf);
            });
        }

        $totals = $query->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        $account = LedgerAccount::where('code', $accountCode)->first();

        /** @var numeric-string $rawDebit */
        $rawDebit = (string) ($totals->total_debit ?? '0');
        /** @var numeric-string $rawCredit */
        $rawCredit = (string) ($totals->total_credit ?? '0');
        $totalDebit = bcadd($rawDebit, '0', 4);
        $totalCredit = bcadd($rawCredit, '0', 4);

        // For debit-normal accounts (assets, expenses): balance = debits - credits
        // For credit-normal accounts (liabilities, equity, revenue): balance = credits - debits
        if ($account !== null && $account->isDebitNormal()) {
            $balance = bcsub($totalDebit, $totalCredit, 4);
        } else {
            $balance = bcsub($totalCredit, $totalDebit, 4);
        }

        return [
            'amount'   => $balance,
            'currency' => (string) config('ledger.default_currency', 'USD'),
        ];
    }

    /**
     * @return array<string, array{debit: string, credit: string, balance: string}>
     */
    public function trialBalance(?Carbon $asOf = null): array
    {
        $accounts = LedgerAccount::active()->orderBy('code')->get();
        $result = [];

        foreach ($accounts as $account) {
            $query = JournalLine::where('account_code', $account->code)
                ->whereHas('entry', function (Builder $q): void {
                    $q->where('status', EntryStatus::POSTED->value);
                });

            if ($asOf !== null) {
                $query->whereHas('entry', function (Builder $q) use ($asOf): void {
                    $q->where('posted_at', '<=', $asOf);
                });
            }

            $totals = $query->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
                ->first();

            /** @var numeric-string $rawDebit */
            $rawDebit = (string) ($totals->total_debit ?? '0');
            /** @var numeric-string $rawCredit */
            $rawCredit = (string) ($totals->total_credit ?? '0');
            $debit = bcadd($rawDebit, '0', 4);
            $credit = bcadd($rawCredit, '0', 4);

            if ($account->isDebitNormal()) {
                $balance = bcsub($debit, $credit, 4);
            } else {
                $balance = bcsub($credit, $debit, 4);
            }

            $result[$account->code] = [
                'debit'   => $debit,
                'credit'  => $credit,
                'balance' => $balance,
            ];
        }

        return $result;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function accountHistory(string $accountCode, Carbon $from, Carbon $to): Collection
    {
        /** @var Collection<int, array<string, mixed>> */
        return JournalLine::where('account_code', $accountCode)
            ->whereHas('entry', function (Builder $q) use ($from, $to): void {
                $q->where('status', EntryStatus::POSTED->value)
                    ->whereBetween('posted_at', [$from, $to]);
            })
            ->with('entry:id,entry_number,description,posted_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn (JournalLine $line): array => [
                'entry_number'  => $line->entry->entry_number,
                'description'   => $line->entry->description,
                'posted_at'     => $line->entry->posted_at?->toIso8601String(),
                'debit_amount'  => (string) $line->debit_amount,
                'credit_amount' => (string) $line->credit_amount,
                'narrative'     => $line->narrative,
            ]);
    }
}
