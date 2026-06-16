<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use App\Infrastructure\Domain\Enums\DomainType;
use Illuminate\Console\Command;

/**
 * Remove a domain safely.
 */
class DomainRemoveCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:remove
        {domain : The domain to remove (e.g., exchange, lending)}
        {--force : Force removal even if other domains depend on this one}
        {--dry-run : Show what would be removed without actually removing}';

    /**
     * @var string
     */
    protected $description = 'Remove a domain (safe removal with dependency check)';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("Removing domain: {$domain}");

        // Check if domain exists
        $normalizedName = str_contains($domain, '/') ? $domain : "finaegis/{$domain}";
        $manifests = $manager->loadAllManifests();

        if (! isset($manifests[$normalizedName])) {
            $this->error("Domain not found: {$domain}");

            return self::FAILURE;
        }

        $manifest = $manifests[$normalizedName];

        // Check if core domain
        if ($manifest->type === DomainType::CORE) {
            $this->error("Cannot remove core domain: {$domain}");
            $this->line('Core domains are required for the system to function.');

            return self::FAILURE;
        }

        // Check for dependents
        $domains = $manager->getAvailableDomains();
        $domainInfo = $domains->firstWhere('name', $normalizedName);

        if ($domainInfo && ! empty($domainInfo->dependents)) {
            $this->newLine();
            $this->warn('The following installed domains depend on this domain:');
            foreach ($domainInfo->dependents as $dependent) {
                $this->line('  - ' . str_replace('finaegis/', '', $dependent));
            }

            if (! $force) {
                $this->newLine();
                $this->error('Cannot remove: other domains depend on this one.');
                $this->line('Use --force to remove anyway (may break dependent domains).');

                return self::FAILURE;
            }

            $this->newLine();
            $this->warn('Force removal requested. Dependent domains may stop working!');
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run mode - no changes made.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Are you sure you want to remove this domain?', false)) {
            $this->info('Removal cancelled.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Removing...');

        $result = $manager->remove($domain, $force);

        if (! $result->success) {
            $this->error('Removal failed:');
            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($result->getSummary());

        if (! empty($result->migrationsReverted)) {
            $this->info('Migrations reverted: ' . count($result->migrationsReverted));
        }

        if (! empty($result->warnings)) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        $this->newLine();
        $this->info('<fg=green>✓</> Domain removed successfully!');

        return self::SUCCESS;
    }
}
