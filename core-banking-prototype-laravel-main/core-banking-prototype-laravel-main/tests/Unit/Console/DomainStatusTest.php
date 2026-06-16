<?php

declare(strict_types=1);

describe('Domain Status Command', function () {
    it('can be instantiated', function () {
        $command = new App\Console\Commands\DomainStatusCommand();
        expect($command)->toBeInstanceOf(App\Console\Commands\DomainStatusCommand::class);
    });

    it('has the correct signature', function () {
        $command = new App\Console\Commands\DomainStatusCommand();
        expect($command->getName())->toBe('domain:status');
    });
});
