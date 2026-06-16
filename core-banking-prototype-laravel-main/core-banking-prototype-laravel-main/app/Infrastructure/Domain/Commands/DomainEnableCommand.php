<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use Illuminate\Console\Command;

/**
 * Enable a previously disabled domain module.
 */
class DomainEnableCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'module:enable {domain : The domain name to enable}';

    /**
     * @var string
     */
    protected $description = 'Enable a disabled domain module';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');

        $result = $manager->enable((string) $domain);

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
