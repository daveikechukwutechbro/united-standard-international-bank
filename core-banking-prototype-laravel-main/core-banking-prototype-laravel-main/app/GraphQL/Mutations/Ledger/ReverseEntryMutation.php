<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Ledger;

use App\Domain\Ledger\Models\JournalEntry;
use App\Domain\Ledger\Services\LedgerService;

final class ReverseEntryMutation
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {
    }

    /**
     * Reverse a posted journal entry.
     *
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): JournalEntry
    {
        return $this->ledgerService->reverse(
            entryId: (string) $args['id'],
            reason: (string) $args['reason'],
        );
    }
}
