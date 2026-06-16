<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DataObjects\DependencyNode;
use App\Infrastructure\Domain\DomainManager;
use Exception;
use Illuminate\Console\Command;

/**
 * Show the dependency tree for a domain.
 */
class DomainDependenciesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:dependencies
        {domain : The domain to show dependencies for}
        {--json : Output as JSON}
        {--flat : Output as flat list}';

    /**
     * @var string
     */
    protected $description = 'Show dependency tree for a domain';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');

        $this->info("Dependency tree for: {$domain}");
        $this->newLine();

        try {
            $tree = $manager->getDependencies($domain);
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $json = json_encode($tree->toArray(), JSON_PRETTY_PRINT);
            $this->line($json !== false ? $json : '{}');

            return self::SUCCESS;
        }

        if ($this->option('flat')) {
            $flat = $tree->flatten();
            foreach ($flat as $dep) {
                $this->line(str_replace('finaegis/', '', $dep));
            }

            return self::SUCCESS;
        }

        $this->renderTree($tree, 0);

        $this->newLine();

        // Check for unsatisfied dependencies
        $unsatisfied = $tree->getUnsatisfied();
        if (! empty($unsatisfied)) {
            $this->warn('Unsatisfied dependencies:');
            foreach ($unsatisfied as $node) {
                $this->line('  - ' . str_replace('finaegis/', '', $node->name));
            }
        } else {
            $this->info('<fg=green>✓</> All dependencies satisfied');
        }

        return self::SUCCESS;
    }

    private function renderTree(DependencyNode $node, int $depth): void
    {
        $prefix = str_repeat('  ', $depth);
        $name = str_replace('finaegis/', '', $node->name);

        $statusIcon = $node->satisfied ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $typeIndicator = $node->required ? '' : ' <fg=gray>(optional)</>';

        if ($depth === 0) {
            $this->line("{$statusIcon} {$name} ({$node->version}){$typeIndicator}");
        } else {
            $branch = '├── ';
            $this->line("{$prefix}{$branch}{$statusIcon} {$name} ({$node->version}){$typeIndicator}");
        }

        foreach ($node->children as $index => $child) {
            $this->renderTree($child, $depth + 1);
        }
    }
}
