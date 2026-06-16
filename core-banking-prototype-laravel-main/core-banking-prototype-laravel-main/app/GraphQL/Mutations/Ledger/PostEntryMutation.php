<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Ledger;

use App\Domain\Ledger\Models\JournalEntry;
use App\Domain\Ledger\Services\LedgerService;

final class PostEntryMutation
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {
    }

    /**
     * Post a new journal entry.
     *
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): JournalEntry
    {
        $inputLines = is_array($args['lines']) ? $args['lines'] : [];

        $lines = [];
        foreach ($inputLines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $entry = [
                'account_code' => (string) ($line['account_code'] ?? ''),
                'debit'        => bcadd(is_numeric($line['debit'] ?? '') ? (string) ($line['debit'] ?? '0') : '0', '0', 4),
                'credit'       => bcadd(is_numeric($line['credit'] ?? '') ? (string) ($line['credit'] ?? '0') : '0', '0', 4),
            ];

            if (isset($line['narrative'])) {
                $entry['narrative'] = (string) $line['narrative'];
            }

            $lines[] = $entry;
        }

        return $this->ledgerService->post(
            description: (string) $args['description'],
            lines: $lines,
            sourceDomain: isset($args['source_domain']) ? (string) $args['source_domain'] : null,
        );
    }
}
