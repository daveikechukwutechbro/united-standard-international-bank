<?php

declare(strict_types=1);

describe('Plugin Verify Command', function () {
    it('can be instantiated', function () {
        $command = new App\Console\Commands\PluginVerifyCommand();
        expect($command)->toBeInstanceOf(App\Console\Commands\PluginVerifyCommand::class);
    });

    it('has the correct signature', function () {
        $command = new App\Console\Commands\PluginVerifyCommand();
        expect($command->getName())->toBe('plugin:verify');
    });
});
