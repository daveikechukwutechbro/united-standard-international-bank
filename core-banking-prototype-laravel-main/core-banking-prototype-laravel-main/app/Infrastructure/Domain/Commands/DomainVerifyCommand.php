<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use App\Infrastructure\Domain\DomainManager;
use Illuminate\Console\Command;

/**
 * Verify a domain's health and configuration.
 */
class DomainVerifyCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:verify
        {domain? : The domain to verify (verifies all if not specified)}
        {--json : Output as JSON}';

    /**
     * @var string
     */
    protected $description = 'Verify domain health and configuration';

    public function handle(DomainManager $manager): int
    {
        $domain = $this->argument('domain');
        $json = $this->option('json');

        if ($domain) {
            return $this->verifySingle($manager, $domain, $json);
        }

        return $this->verifyAll($manager, $json);
    }

    private function verifySingle(DomainManager $manager, string $domain, bool $json): int
    {
        $result = $manager->verify($domain);

        if ($json) {
            $jsonOutput = json_encode($result->toArray(), JSON_PRETTY_PRINT);
            $this->line($jsonOutput !== false ? $jsonOutput : '{}');

            return $result->valid ? self::SUCCESS : self::FAILURE;
        }

        $this->info("Verifying domain: {$domain}");
        $this->newLine();

        // Display check results
        foreach ($result->checks as $check => $passed) {
            $icon = $passed ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $checkName = str_replace('_', ' ', ucfirst($check));
            $this->line("  {$icon} {$checkName}");
        }

        $this->newLine();

        if (! empty($result->errors)) {
            $this->error('Errors:');
            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();
        }

        if (! empty($result->warnings)) {
            $this->warn('Warnings:');
            foreach ($result->warnings as $warning) {
                $this->line("  - {$warning}");
            }
            $this->newLine();
        }

        if ($result->valid) {
            $this->info("<fg=green>✓</> Domain {$domain} is healthy ({$result->getPassedCount()}/{$result->getPassedCount()} checks passed)");

            return self::SUCCESS;
        }

        $this->error("Domain {$domain} has issues ({$result->getFailedCount()} checks failed)");

        return self::FAILURE;
    }

    private function verifyAll(DomainManager $manager, bool $json): int
    {
        $manifests = $manager->loadAllManifests();
        $results = [];
        $allValid = true;

        foreach (array_keys($manifests) as $domainName) {
            $result = $manager->verify($domainName);
            $results[$domainName] = $result;
            if (! $result->valid) {
                $allValid = false;
            }
        }

        if ($json) {
            $output = [];
            foreach ($results as $name => $result) {
                $output[$name] = $result->toArray();
            }
            $jsonOutput = json_encode($output, JSON_PRETTY_PRINT);
            $this->line($jsonOutput !== false ? $jsonOutput : '{}');

            return $allValid ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Domain Health Check');
        $this->newLine();

        $headers = ['Domain', 'Status', 'Passed', 'Failed', 'Issues'];
        $rows = [];

        foreach ($results as $name => $result) {
            $shortName = str_replace('finaegis/', '', $name);
            $statusIcon = $result->valid ? '<fg=green>✓</>' : '<fg=red>✗</>';

            $issues = [];
            if (! empty($result->errors)) {
                $issues[] = count($result->errors) . ' error(s)';
            }
            if (! empty($result->warnings)) {
                $issues[] = count($result->warnings) . ' warning(s)';
            }

            $rows[] = [
                $shortName,
                $statusIcon . ' ' . ($result->valid ? 'OK' : 'FAIL'),
                $result->getPassedCount(),
                $result->getFailedCount(),
                empty($issues) ? '-' : implode(', ', $issues),
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();

        $valid = collect($results)->filter(fn ($r) => $r->valid)->count();
        $total = count($results);

        if ($allValid) {
            $this->info("<fg=green>✓</> All {$total} domains are healthy!");

            return self::SUCCESS;
        }

        $this->warn("{$valid}/{$total} domains are healthy. Run `domain:verify <domain>` for details.");

        return self::FAILURE;
    }
}
