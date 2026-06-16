<?php

declare(strict_types=1);

describe('event:compact command', function () {
    it('runs with --dry-run flag', function () {
        $this->artisan('event:compact --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('runs with --domain flag and --dry-run', function () {
        $this->artisan('event:compact --domain=Monitoring --dry-run')
            ->assertSuccessful();
    });

    it('fails for unknown domain', function () {
        $this->artisan('event:compact --domain=NonExistentDomain --dry-run')
            ->expectsOutputToContain('not found')
            ->assertFailed();
    });

    it('accepts --keep-latest parameter', function () {
        $this->artisan('event:compact --keep-latest=50 --dry-run')
            ->expectsOutputToContain('50')
            ->assertSuccessful();
    });

    it('has correct command signature', function () {
        $command = $this->app->make(Illuminate\Contracts\Console\Kernel::class)
            ->all()['event:compact'] ?? null;

        expect($command)->not->toBeNull();
        expect($command->getDefinition()->hasOption('domain'))->toBeTrue();
        expect($command->getDefinition()->hasOption('keep-latest'))->toBeTrue();
        expect($command->getDefinition()->hasOption('dry-run'))->toBeTrue();
    });
});
