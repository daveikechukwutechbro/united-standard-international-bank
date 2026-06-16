<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Microfinance\Services\LoanProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class MfiRunProvisioningCommand extends Command
{
    protected $signature = 'mfi:run-provisioning';

    protected $description = 'Reclassify all active loan provisions and display results';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(LoanProvisioningService $loanProvisioningService): int
    {
        $this->info('Running loan provisioning reclassification...');

        try {
            $count = $loanProvisioningService->reclassifyAll();

            $this->info("Reclassified {$count} loan provision(s).");

            // Display totals summary
            $totals = $loanProvisioningService->getTotalProvisions();

            $this->table(
                ['Category', 'Amount'],
                [
                    ['Standard',    $totals['standard']],
                    ['Substandard', $totals['substandard']],
                    ['Doubtful',    $totals['doubtful']],
                    ['Loss',        $totals['loss']],
                    ['Total',       $totals['total']],
                ],
            );

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Provisioning failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
