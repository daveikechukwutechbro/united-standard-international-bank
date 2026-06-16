<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('EventStatsCommand', function () {
    it('displays all stats in table format', function () {
        $this->artisan('event:stats')
            ->expectsOutput('Gathering event store statistics...')
            ->expectsOutput('Event Store Summary')
            ->expectsOutput('Domain Table Mapping')
            ->assertSuccessful();
    });

    it('displays stats in json format', function () {
        $this->artisan('event:stats --format=json')
            ->expectsOutput('Gathering event store statistics...')
            ->assertSuccessful();
    });

    it('displays domain-specific stats', function () {
        $this->artisan('event:stats --domain=Account')
            ->expectsOutput('Gathering event store statistics...')
            ->assertSuccessful();
    });

    it('displays domain stats in json format', function () {
        $this->artisan('event:stats --domain=Account --format=json')
            ->expectsOutput('Gathering event store statistics...')
            ->assertSuccessful();
    });

    it('fails for unknown domain', function () {
        $this->artisan('event:stats --domain=NonExistent')
            ->expectsOutput('Gathering event store statistics...')
            ->expectsOutput('Unknown domain: NonExistent')
            ->assertFailed();
    });

    it('has correct command signature', function () {
        $command = new App\Console\Commands\EventStatsCommand();

        expect($command->getName())->toBe('event:stats');
        expect($command->getDescription())->toBe('Display event store statistics and growth metrics');
    });
});
