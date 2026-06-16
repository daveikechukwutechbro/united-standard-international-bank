<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ledger\Services\ChartOfAccountsService;
use App\Domain\Ledger\Services\LedgerService;
use Illuminate\Console\Command;
use Throwable;

class BenchmarkLedgerCommand extends Command
{
    protected $signature = 'benchmark:ledger {--count=100 : Number of entries to post}';

    protected $description = 'Benchmark GL posting throughput';

    public function handle(LedgerService $ledgerService, ChartOfAccountsService $chartService): int
    {
        $count = (int) $this->option('count');
        $this->info("Benchmarking {$count} journal entries...");

        // Ensure chart of accounts exists
        $chartService->seedDefaultAccounts();

        $start = microtime(true);
        $errors = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                $amount = number_format(random_int(100, 100000) / 100, 4, '.', '');
                $ledgerService->post(
                    "Benchmark entry #{$i}",
                    [
                        ['account_code' => '1110', 'debit' => $amount, 'credit' => '0.0000'],
                        ['account_code' => '2100', 'debit' => '0.0000', 'credit' => $amount],
                    ],
                    'Benchmark',
                );
            } catch (Throwable $e) {
                $errors++;
            }
        }

        $elapsed = microtime(true) - $start;
        $rate = $elapsed > 0 ? $count / $elapsed : 0;

        $this->table(['Metric', 'Value'], [
            ['Entries', $count],
            ['Errors', $errors],
            ['Time (seconds)', number_format($elapsed, 2)],
            ['Entries/second', number_format($rate, 1)],
        ]);

        return self::SUCCESS;
    }
}
