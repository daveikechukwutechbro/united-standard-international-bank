<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\FinancialInstitution\Services\SandboxProvisioningService;
use Illuminate\Console\Command;

class PartnerSandboxCreateCommand extends Command
{
    protected $signature = 'partner:sandbox:create {partner} {--profile=basic : Seed profile (basic, full, payments)}';

    protected $description = 'Create a sandbox environment for a partner';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(SandboxProvisioningService $service): int
    {
        $partnerId = $this->argument('partner');
        $profile = $this->option('profile');

        $result = $service->createSandbox((string) $partnerId, (string) $profile);

        $this->info("Sandbox created: {$result['sandbox_id']}");
        $this->info("API Key: {$result['api_key']}");
        $this->info("Profile: {$result['profile']}");
        $this->table(
            ['Resource', 'Count'],
            collect($result['seed_counts'])->map(fn ($count, $key) => [$key, $count])->values()->toArray()
        );

        return self::SUCCESS;
    }
}
