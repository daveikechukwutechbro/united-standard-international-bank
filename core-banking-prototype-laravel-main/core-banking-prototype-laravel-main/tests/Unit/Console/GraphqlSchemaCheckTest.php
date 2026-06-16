<?php

declare(strict_types=1);

describe('GraphQL Schema Check Command', function () {
    it('can be instantiated', function () {
        $command = new App\Console\Commands\GraphqlSchemaCheckCommand();
        expect($command)->toBeInstanceOf(App\Console\Commands\GraphqlSchemaCheckCommand::class);
    });

    it('has the correct signature', function () {
        $command = new App\Console\Commands\GraphqlSchemaCheckCommand();
        expect($command->getName())->toBe('graphql:schema-check');
    });
});
