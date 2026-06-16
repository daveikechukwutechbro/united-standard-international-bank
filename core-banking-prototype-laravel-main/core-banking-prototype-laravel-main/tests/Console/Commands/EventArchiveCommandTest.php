<?php

declare(strict_types=1);

describe('event:archive command', function () {
    it('runs with --dry-run flag', function () {
        $this->artisan('event:archive --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('runs with --domain flag and --dry-run', function () {
        $this->artisan('event:archive --domain=Account --dry-run')
            ->assertSuccessful();
    });

    it('fails for unknown domain', function () {
        $this->artisan('event:archive --domain=NonExistentDomain --dry-run')
            ->expectsOutputToContain('not found')
            ->assertFailed();
    });

    it('accepts --before date parameter', function () {
        $this->artisan('event:archive --before=2025-01-01 --dry-run')
            ->expectsOutputToContain('2025-01-01')
            ->assertSuccessful();
    });

    it('accepts --batch-size parameter', function () {
        $this->artisan('event:archive --batch-size=500 --dry-run')
            ->expectsOutputToContain('500')
            ->assertSuccessful();
    });

    it('has correct command signature', function () {
        $command = $this->app->make(Illuminate\Contracts\Console\Kernel::class)
            ->all()['event:archive'] ?? null;

        expect($command)->not->toBeNull();
        expect($command->getDefinition()->hasOption('before'))->toBeTrue();
        expect($command->getDefinition()->hasOption('domain'))->toBeTrue();
        expect($command->getDefinition()->hasOption('batch-size'))->toBeTrue();
        expect($command->getDefinition()->hasOption('dry-run'))->toBeTrue();
    });
});
