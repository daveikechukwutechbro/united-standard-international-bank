<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use Illuminate\Console\Command;

/**
 * Disable a domain module without reverting migrations.
 */
class DomainDisableCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'module:disable {domain : The domain name to disable}';

    /**
     * @var string
     */
    protected $description = 'Disable a domain module (routes will stop loading, migrations are preserved)';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');

        $result = $manager->disable((string) $domain);

        if (! $result->success) {
            $this->error($result->getSummary());

            return self::FAILURE;
        }

        $this->info($result->getSummary());

        if (! empty($result->warnings)) {
            foreach ($result->warnings as $warning) {
                $this->warn("  Warning: {$warning}");
            }
        }

        return self::SUCCESS;
    }
}
