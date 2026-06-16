<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use App\Infrastructure\Domain\Enums\DomainStatus;
use App\Infrastructure\Domain\Enums\DomainType;
use Illuminate\Console\Command;

/**
 * List all available domains with their status and dependencies.
 */
class DomainListCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:list
        {--type= : Filter by type (core, optional)}
        {--status= : Filter by status (installed, available, disabled, missing_dependencies)}
        {--json : Output as JSON}';

    /**
     * @var string
     */
    protected $description = 'List all available domains with dependency information';

    public function handle(DomainManager $manager): int
    {
        $domains = $manager->getAvailableDomains();

        // Apply filters
        $typeFilter = $this->option('type');
        $statusFilter = $this->option('status');

        if ($typeFilter) {
            $type = DomainType::tryFrom($typeFilter);
            if ($type === null) {
                $this->error("Invalid type: {$typeFilter}. Valid types: " . implode(', ', DomainType::values()));

                return self::FAILURE;
            }
            $domains = $domains->filter(fn ($d) => $d->type === $type);
        }

        if ($statusFilter) {
            $status = DomainStatus::tryFrom($statusFilter);
            if ($status === null) {
                $this->error("Invalid status: {$statusFilter}. Valid statuses: " . implode(', ', DomainStatus::values()));

                return self::FAILURE;
            }
            $domains = $domains->filter(fn ($d) => $d->status === $status);
        }

        if ($this->option('json')) {
            $json = json_encode($domains->map->toArray()->toArray(), JSON_PRETTY_PRINT);
            $this->line($json !== false ? $json : '[]');

            return self::SUCCESS;
        }

        if ($domains->isEmpty()) {
            $this->info('No domains found matching the criteria.');

            return self::SUCCESS;
        }

        $this->info('FinAegis Domain Registry');
        $this->newLine();

        $headers = ['Domain', 'Type', 'Status', 'Version', 'Dependencies'];
        $rows = [];

        foreach ($domains as $domain) {
            $statusIcon = match ($domain->status) {
                DomainStatus::INSTALLED    => '<fg=green>✓</>',
                DomainStatus::AVAILABLE    => '<fg=yellow>○</>',
                DomainStatus::DISABLED     => '<fg=gray>-</>',
                DomainStatus::MISSING_DEPS => '<fg=red>✗</>',
            };

            $typeLabel = match ($domain->type) {
                DomainType::CORE     => '<fg=cyan>core</>',
                DomainType::OPTIONAL => 'optional',
            };

            $deps = empty($domain->dependencies)
                ? '-'
                : implode(', ', array_map(
                    fn ($d) => str_replace('finaegis/', '', $d),
                    $domain->dependencies
                ));

            $rows[] = [
                $statusIcon . ' ' . str_replace('finaegis/', '', $domain->name),
                $typeLabel,
                $domain->status->value,
                $domain->version,
                strlen($deps) > 40 ? substr($deps, 0, 37) . '...' : $deps,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->line('<fg=green>✓</> Installed  <fg=yellow>○</> Available  <fg=red>✗</> Missing dependencies');

        // Summary
        $installed = $domains->filter(fn ($d) => $d->status === DomainStatus::INSTALLED)->count();
        $available = $domains->filter(fn ($d) => $d->status === DomainStatus::AVAILABLE)->count();
        $this->newLine();
        $this->info("Total: {$domains->count()} domains ({$installed} installed, {$available} available)");

        return self::SUCCESS;
    }
}
