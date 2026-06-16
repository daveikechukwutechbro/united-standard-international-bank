<?php

declare(strict_types=1);

describe('EventReplayCommand Filters', function () {
    it('has event-type filter option in signature', function () {
        $command = new App\Console\Commands\EventReplayCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('event-type'))->toBeTrue();
        expect($definition->getOption('event-type')->getDescription())
            ->toContain('event classes');
    });

    it('has aggregate-id filter option in signature', function () {
        $command = new App\Console\Commands\EventReplayCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('aggregate-id'))->toBeTrue();
        expect($definition->getOption('aggregate-id')->getDescription())
            ->toContain('aggregate UUID');
    });

    it('retains existing filter options', function () {
        $command = new App\Console\Commands\EventReplayCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('domain'))->toBeTrue();
        expect($definition->hasOption('projector'))->toBeTrue();
        expect($definition->hasOption('from'))->toBeTrue();
        expect($definition->hasOption('to'))->toBeTrue();
        expect($definition->hasOption('dry-run'))->toBeTrue();
    });
});
