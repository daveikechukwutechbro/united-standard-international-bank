<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventStoreHealthCheck;

describe('PerformSystemHealthChecks --deep', function () {
    it('accepts the --deep flag', function () {
        $this->artisan('system:health-check --deep')
            ->assertSuccessful();
    });

    it('outputs deep health check results with --deep flag', function () {
        $this->artisan('system:health-check --deep')
            ->expectsOutputToContain('deep event store health checks')
            ->assertSuccessful();
    });

    it('runs without --deep flag as before', function () {
        $this->artisan('system:health-check')
            ->doesntExpectOutputToContain('deep event store health checks')
            ->assertSuccessful();
    });

    it('can combine --service and --deep flags', function () {
        $this->artisan('system:health-check --service=database --deep')
            ->assertSuccessful();
    });

    it('resolves EventStoreHealthCheck from container', function () {
        $healthCheck = app(EventStoreHealthCheck::class);

        expect($healthCheck)->toBeInstanceOf(EventStoreHealthCheck::class);
    });

    it('has the --deep option in command signature', function () {
        $command = $this->app->make(Illuminate\Contracts\Console\Kernel::class)
            ->all()['system:health-check'] ?? null;

        expect($command)->not->toBeNull();
        expect($command->getDefinition()->hasOption('deep'))->toBeTrue();
    });
});
