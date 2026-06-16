<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use Illuminate\Console\Command;

/**
 * Install a domain with its dependencies.
 */
class DomainInstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:install
        {domain : The domain to install (e.g., exchange, lending)}
        {--no-dependencies : Do not automatically install dependencies}
        {--dry-run : Show what would be installed without actually installing}';

    /**
     * @var string
     */
    protected $description = 'Install a domain with its dependencies';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');
        $withDependencies = ! $this->option('no-dependencies');
        $dryRun = $this->option('dry-run');

        $this->info("Installing domain: {$domain}");

        // Check if domain exists
        $normalizedName = str_contains($domain, '/') ? $domain : "finaegis/{$domain}";
        $manifests = $manager->loadAllManifests();

        if (! isset($manifests[$normalizedName])) {
            $this->error("Domain not found: {$domain}");
            $this->newLine();
            $this->info('Available domains:');
            foreach (array_keys($manifests) as $name) {
                $this->line('  - ' . str_replace('finaegis/', '', $name));
            }

            return self::FAILURE;
        }

        $manifest = $manifests[$normalizedName];

        // Show what will be installed
        if (! empty($manifest->requiredDependencies)) {
            $this->newLine();
            $this->info('Required dependencies:');
            foreach ($manifest->requiredDependencies as $dep => $version) {
                $depName = str_replace('finaegis/', '', $dep);
                $this->line("  - {$depName} ({$version})");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run mode - no changes made.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Do you want to proceed with the installation?', true)) {
            $this->info('Installation cancelled.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Installing...');

        $result = $manager->install($domain, $withDependencies);

        if (! $result->success) {
            $this->error('Installation failed:');
            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($result->getSummary());

        if (! empty($result->installedDependencies)) {
            $this->newLine();
            $this->info('Installed dependencies:');
            foreach ($result->installedDependencies as $dep) {
                $this->line('  <fg=green>✓</> ' . str_replace('finaegis/', '', $dep));
            }
        }

        if (! empty($result->migrationsRun)) {
            $this->newLine();
            $this->info('Migrations run: ' . count($result->migrationsRun));
        }

        if (! empty($result->warnings)) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        $this->newLine();
        $this->info('<fg=green>✓</> Domain installed successfully!');

        return self::SUCCESS;
    }
}
