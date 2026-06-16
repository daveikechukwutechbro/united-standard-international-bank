<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\FinancialInstitution\Services\SandboxResetService;
use Illuminate\Console\Command;

class PartnerSandboxResetCommand extends Command
{
    protected $signature = 'partner:sandbox:reset {sandbox} {--profile=basic : Seed profile to apply after reset (basic, full, payments)}';

    protected $description = 'Reset a sandbox environment to a clean state and re-seed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(SandboxResetService $service): int
    {
        $sandboxId = $this->argument('sandbox');
        $profile = $this->option('profile');

        if (! $this->confirm("Reset sandbox {$sandboxId}? All data will be cleared.")) {
            $this->info('Reset cancelled.');

            return self::SUCCESS;
        }

        $result = $service->reset((string) $sandboxId, (string) $profile);

        if ($result['reset']) {
            $this->info("Sandbox {$result['sandbox_id']} reset successfully.");
            $this->info("Profile applied: {$result['profile']}");
        } else {
            $this->error("Failed to reset sandbox {$sandboxId}.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
