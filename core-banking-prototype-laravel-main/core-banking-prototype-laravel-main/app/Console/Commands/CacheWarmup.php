<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Account\Models\Account;
use App\Domain\Account\Services\Cache\CacheManager;
use Illuminate\Console\Command;

class CacheWarmup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup {--account=* : Specific account UUIDs to warm up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up Redis cache for accounts';

    /**
     * Execute the console command.
     */
    public function handle(CacheManager $cacheManager): int
    {
        $accountUuids = $this->option('account');

        if (empty($accountUuids)) {
            // Warm up cache for all active accounts
            $this->info('Warming up cache for all accounts...');

            Account::query()
                ->where('frozen', false)
                ->chunk(
                    100,
                    function ($accounts) use ($cacheManager) {
                        foreach ($accounts as $account) {
                            $cacheManager->warmUp($account->uuid);
                            $this->info("Warmed up cache for account: {$account->uuid}");
                        }
                    }
                );
        } else {
            // Warm up specific accounts
            foreach ($accountUuids as $uuid) {
                $this->info("Warming up cache for account: {$uuid}");
                $cacheManager->warmUp($uuid);
            }
        }

        $this->info('Cache warmup completed!');

        return Command::SUCCESS;
    }
}
