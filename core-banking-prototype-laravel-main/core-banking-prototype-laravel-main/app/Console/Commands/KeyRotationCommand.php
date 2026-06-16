<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\KeyRotationService;
use Illuminate\Console\Command;

class KeyRotationCommand extends Command
{
    protected $signature = 'key:rotate
        {type? : Key type to rotate (app_key, encryption_key, jwt_secret, api_token, session_key, webhook_secret)}
        {--dry-run : Preview rotation without executing}
        {--force : Force rotation even if not overdue}
        {--status : Show rotation status only}
        {--init : Initialize default key inventory}';

    protected $description = 'Manage cryptographic key rotation schedules';

    public function handle(KeyRotationService $keyRotationService): int
    {
        if ($this->option('init')) {
            return $this->initializeKeys($keyRotationService);
        }

        if ($this->option('status')) {
            return $this->showStatus($keyRotationService);
        }

        $type = $this->argument('type');

        if (! $type) {
            return $this->rotateOverdue($keyRotationService);
        }

        return $this->rotateSpecific($keyRotationService, $type);
    }

    private function initializeKeys(KeyRotationService $service): int
    {
        $this->info('Initializing default key inventory...');
        $result = $service->initializeDefaultKeys();
        $this->info("Registered {$result['registered']} of {$result['total']} keys.");

        return Command::SUCCESS;
    }

    private function showStatus(KeyRotationService $service): int
    {
        $report = $service->generateRotationReport();

        $this->info('Key Rotation Status');
        $this->info('===================');
        $this->newLine();
        $this->line("Total keys tracked: {$report['total_keys']}");
        $this->line("Active: {$report['active']}");

        $overdueColor = $report['overdue'] > 0 ? 'red' : 'green';
        $this->line("Overdue: <fg={$overdueColor}>{$report['overdue']}</>");
        $this->line("Due soon: {$report['due_soon']}");
        $this->line("Compliance rate: {$report['compliance_rate']}%");
        $this->newLine();

        if (! empty($report['by_type'])) {
            $this->info('By Key Type:');
            foreach ($report['by_type'] as $type => $data) {
                $overdueInfo = $data['overdue'] > 0 ? " (<fg=red>{$data['overdue']} overdue</>)" : '';
                $this->line("  {$type}: {$data['count']} keys{$overdueInfo}");
            }
        }

        $this->newLine();

        // Show overdue keys
        $overdueKeys = $service->getOverdueKeys();
        if ($overdueKeys->isNotEmpty()) {
            $this->warn('Overdue Keys:');
            foreach ($overdueKeys as $key) {
                $this->line("  - {$key->key_identifier} ({$key->key_type}): overdue since {$key->next_rotation_at->diffForHumans()}");
            }
        }

        // Show keys due soon
        $dueSoonKeys = $service->getKeysDueSoon();
        if ($dueSoonKeys->isNotEmpty()) {
            $this->info('Keys Due Soon:');
            foreach ($dueSoonKeys as $key) {
                $this->line("  - {$key->key_identifier} ({$key->key_type}): due {$key->next_rotation_at->diffForHumans()}");
            }
        }

        return Command::SUCCESS;
    }

    private function rotateOverdue(KeyRotationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $overdueKeys = $service->getOverdueKeys();

        if ($overdueKeys->isEmpty()) {
            $this->info('No keys are overdue for rotation.');

            return Command::SUCCESS;
        }

        $this->info("Found {$overdueKeys->count()} overdue key(s).");

        if ($dryRun) {
            $this->warn('[DRY RUN] The following keys would be rotated:');
        }

        foreach ($overdueKeys as $key) {
            $result = $service->rotateKey($key->key_identifier, $dryRun);

            if ($result['success']) {
                $prefix = $dryRun ? '[DRY RUN] Would rotate' : 'Rotated';
                $this->info("{$prefix}: {$key->key_identifier} ({$key->key_type})");
            } else {
                $this->error("Failed to rotate {$key->key_identifier}: {$result['message']}");
            }
        }

        return Command::SUCCESS;
    }

    private function rotateSpecific(KeyRotationService $service, string $type): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keys = $service->getKeyInventory()->where('key_type', $type);

        if ($keys->isEmpty()) {
            $this->error("No keys found for type: {$type}");

            return Command::FAILURE;
        }

        foreach ($keys as $key) {
            if (! $key->isOverdue() && ! $this->option('force')) {
                $this->warn("Key {$key->key_identifier} is not overdue. Use --force to rotate anyway.");

                continue;
            }

            $result = $service->rotateKey($key->key_identifier, $dryRun);

            if ($result['success']) {
                $prefix = $dryRun ? '[DRY RUN] Would rotate' : 'Rotated';
                $this->info("{$prefix}: {$key->key_identifier}");
            } else {
                $this->error("Failed: {$result['message']}");
            }
        }

        return Command::SUCCESS;
    }
}
