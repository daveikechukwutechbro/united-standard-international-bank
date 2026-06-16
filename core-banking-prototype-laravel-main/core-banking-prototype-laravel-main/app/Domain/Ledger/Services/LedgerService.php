<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services;

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Enums\EntryStatus;
use App\Domain\Ledger\Models\JournalEntry;
use App\Domain\Ledger\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class LedgerService
{
    public function __construct(
        private readonly LedgerDriverInterface $driver,
    ) {
    }

    /**
     * Create and post a balanced journal entry.
     *
     * @param array<int, array{account_code: string, debit: numeric-string, credit: numeric-string, narrative?: string}> $lines
     */
    public function post(
        string $description,
        array $lines,
        ?string $sourceDomain = null,
        ?string $sourceEventId = null,
    ): JournalEntry {
        // Validate double-entry invariant
        /** @var numeric-string $totalDebit */
        $totalDebit = '0';
        /** @var numeric-string $totalCredit */
        $totalCredit = '0';
        foreach ($lines as $line) {
            $totalDebit = bcadd($totalDebit, $line['debit'], 4);
            $totalCredit = bcadd($totalCredit, $line['credit'], 4);
        }

        if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
            throw new RuntimeException(
                "Journal entry is unbalanced: debits={$totalDebit}, credits={$totalCredit}"
            );
        }

        if (bccomp($totalDebit, '0', 4) === 0) {
            throw new RuntimeException('Journal entry has zero total — at least one line must have a non-zero amount');
        }

        $entry = DB::transaction(function () use ($description, $lines, $sourceDomain, $sourceEventId): JournalEntry {
            $entry = JournalEntry::create([
                'entry_number'    => 'JE-' . strtoupper(Str::random(8)),
                'description'     => $description,
                'status'          => EntryStatus::POSTED,
                'posted_at'       => Carbon::now(),
                'source_domain'   => $sourceDomain,
                'source_event_id' => $sourceEventId,
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code'     => $line['account_code'],
                    'debit_amount'     => $line['debit'],
                    'credit_amount'    => $line['credit'],
                    'currency'         => (string) config('ledger.default_currency', 'USD'),
                    'narrative'        => $line['narrative'] ?? null,
                ]);
            }

            $entry->load('lines');

            return $entry;
        });

        $this->driver->post($entry);

        return $entry;
    }

    /**
     * Reverse a posted journal entry by creating a mirror entry.
     */
    public function reverse(string $entryId, string $reason): JournalEntry
    {
        $original = JournalEntry::with('lines')->findOrFail($entryId);

        if ($original->status !== EntryStatus::POSTED) {
            throw new RuntimeException("Cannot reverse entry with status: {$original->status->value}");
        }

        // Create reversal lines (swap debits and credits)
        /** @var array<int, array{account_code: string, debit: numeric-string, credit: numeric-string, narrative?: string}> $reversalLines */
        $reversalLines = [];
        foreach ($original->lines as $line) {
            /** @var numeric-string $creditAmt */
            $creditAmt = (string) $line->credit_amount;
            /** @var numeric-string $debitAmt */
            $debitAmt = (string) $line->debit_amount;
            $reversalLines[] = [
                'account_code' => $line->account_code,
                'debit'        => bcadd($creditAmt, '0', 4),
                'credit'       => bcadd($debitAmt, '0', 4),
                'narrative'    => "Reversal: {$reason}",
            ];
        }

        $reversal = $this->post(
            "Reversal of {$original->entry_number}: {$reason}",
            $reversalLines,
            $original->source_domain,
            $original->source_event_id,
        );

        $original->update([
            'status'      => EntryStatus::REVERSED,
            'reversed_by' => $reversal->id,
        ]);

        return $reversal;
    }

    /**
     * Get the balance for a specific account.
     *
     * @return array{amount: string, currency: string}
     */
    public function getBalance(string $accountCode, ?Carbon $asOf = null): array
    {
        return $this->driver->balance($accountCode, $asOf);
    }

    /**
     * Get the full trial balance.
     *
     * @return array<string, array{debit: string, credit: string, balance: string}>
     */
    public function getTrialBalance(?Carbon $asOf = null): array
    {
        return $this->driver->trialBalance($asOf);
    }

    /**
     * Get account transaction history.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAccountHistory(string $accountCode, Carbon $from, Carbon $to): Collection
    {
        return $this->driver->accountHistory($accountCode, $from, $to);
    }
}
