<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

describe('EventMigrateCommand', function () {
    it('rejects batch size less than 1', function () {
        $this->artisan('event:migrate', ['--batch' => 0, '--dry-run' => true])
            ->expectsOutput('Batch size must be at least 1.')
            ->assertExitCode(1);
    });

    it('rejects negative batch size', function () {
        $this->artisan('event:migrate', ['--batch' => -5, '--dry-run' => true])
            ->expectsOutput('Batch size must be at least 1.')
            ->assertExitCode(1);
    });
});
