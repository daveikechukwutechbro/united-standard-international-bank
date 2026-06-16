<?php

declare(strict_types=1);

describe('EventRebuildCommand', function () {
    it('fails for unknown aggregate', function () {
        $this->artisan('event:rebuild NonExistentAggregate')
            ->expectsOutput('Unknown aggregate: NonExistentAggregate')
            ->expectsOutput('Available aggregates:')
            ->assertFailed();
    });

    it('lists available aggregates on failure', function () {
        $this->artisan('event:rebuild InvalidAggregate')
            ->expectsOutputToContain('TransactionAggregate')
            ->assertFailed();
    });

    it('has correct command signature', function () {
        $command = new App\Console\Commands\EventRebuildCommand();

        expect($command->getName())->toBe('event:rebuild');
        expect($command->getDescription())->toBe('Rebuild aggregate state by replaying its events');
    });

    it('has proper inheritance', function () {
        $reflection = new ReflectionClass(App\Console\Commands\EventRebuildCommand::class);
        expect($reflection->getParentClass()->getName())->toBe('Illuminate\Console\Command');
    });
});
