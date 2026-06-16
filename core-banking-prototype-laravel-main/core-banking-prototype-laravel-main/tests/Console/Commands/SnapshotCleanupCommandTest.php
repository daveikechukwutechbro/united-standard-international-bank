<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('SnapshotCleanupCommand', function () {
    it('runs dry run for all domains', function () {
        $this->artisan('snapshot:cleanup --dry-run')
            ->expectsOutput('DRY RUN - No snapshots will be deleted.')
            ->expectsOutputToContain('Cleaning up snapshots older than 30 days')
            ->expectsOutputToContain('Dry run complete.')
            ->assertSuccessful();
    });

    it('runs dry run for specific domain', function () {
        $this->artisan('snapshot:cleanup --domain=Account --dry-run')
            ->expectsOutput('DRY RUN - No snapshots will be deleted.')
            ->expectsOutputToContain('transaction_snapshots')
            ->assertSuccessful();
    });

    it('accepts custom days parameter', function () {
        $this->artisan('snapshot:cleanup --days=60 --dry-run')
            ->expectsOutput('DRY RUN - No snapshots will be deleted.')
            ->expectsOutputToContain('Cleaning up snapshots older than 60 days')
            ->assertSuccessful();
    });

    it('fails for domain with no snapshot table', function () {
        $this->artisan('snapshot:cleanup --domain=Exchange --dry-run')
            ->expectsOutput('No snapshot table found for domain: Exchange')
            ->assertFailed();
    });

    it('processes cleanup without dry run', function () {
        $this->artisan('snapshot:cleanup --days=30')
            ->expectsOutputToContain('Cleaning up snapshots older than 30 days')
            ->expectsOutputToContain('Snapshot cleanup complete.')
            ->assertSuccessful();
    });

    it('has correct command signature', function () {
        $command = new App\Console\Commands\SnapshotCleanupCommand();

        expect($command->getName())->toBe('snapshot:cleanup');
        expect($command->getDescription())->toBe('Clean up old snapshots while retaining the latest per aggregate');
    });

    it('has proper inheritance', function () {
        $reflection = new ReflectionClass(App\Console\Commands\SnapshotCleanupCommand::class);
        expect($reflection->getParentClass()->getName())->toBe('Illuminate\Console\Command');
    });
});
