<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Contracts;

use App\Domain\Ledger\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface LedgerDriverInterface
{
    public function post(JournalEntry $entry): void;

    /**
     * @return array{amount: string, currency: string}
     */
    public function balance(string $accountCode, ?Carbon $asOf = null): array;

    /**
     * @return array<string, array{debit: string, credit: string, balance: string}>
     */
    public function trialBalance(?Carbon $asOf = null): array;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function accountHistory(string $accountCode, Carbon $from, Carbon $to): Collection;
}
