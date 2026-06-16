<?php

declare(strict_types=1);

use App\Console\Commands\MfiCreateGroupCommand;
use App\Console\Commands\MfiGenerateCollectionSheetsCommand;
use App\Console\Commands\MfiRunProvisioningCommand;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// MfiCreateGroupCommand — structural tests
// ---------------------------------------------------------------------------

it('MfiCreateGroupCommand class exists', function (): void {
    expect(class_exists(MfiCreateGroupCommand::class))->toBeTrue();
});

it('MfiCreateGroupCommand has correct signature name', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    expect($command->getName())->toBe('mfi:create-group');
});

it('MfiCreateGroupCommand has correct description', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    expect($command->getDescription())->not->toBeEmpty();
});

it('MfiCreateGroupCommand requires name argument', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    $definition = $command->getDefinition();
    expect($definition->hasArgument('name'))->toBeTrue();
});

it('MfiCreateGroupCommand has frequency option defaulting to weekly', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    $definition = $command->getDefinition();
    expect($definition->hasOption('frequency'))->toBeTrue();
    expect($definition->getOption('frequency')->getDefault())->toBe('weekly');
});

it('MfiCreateGroupCommand has center option', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    $definition = $command->getDefinition();
    expect($definition->hasOption('center'))->toBeTrue();
});

it('MfiCreateGroupCommand has meeting-day option', function (): void {
    $command = $this->app->make(MfiCreateGroupCommand::class);
    $definition = $command->getDefinition();
    expect($definition->hasOption('meeting-day'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// MfiGenerateCollectionSheetsCommand — structural tests
// ---------------------------------------------------------------------------

it('MfiGenerateCollectionSheetsCommand class exists', function (): void {
    expect(class_exists(MfiGenerateCollectionSheetsCommand::class))->toBeTrue();
});

it('MfiGenerateCollectionSheetsCommand has correct signature name', function (): void {
    $command = $this->app->make(MfiGenerateCollectionSheetsCommand::class);
    expect($command->getName())->toBe('mfi:generate-collection-sheets');
});

it('MfiGenerateCollectionSheetsCommand has date option', function (): void {
    $command = $this->app->make(MfiGenerateCollectionSheetsCommand::class);
    $definition = $command->getDefinition();
    expect($definition->hasOption('date'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// MfiRunProvisioningCommand — structural tests
// ---------------------------------------------------------------------------

it('MfiRunProvisioningCommand class exists', function (): void {
    expect(class_exists(MfiRunProvisioningCommand::class))->toBeTrue();
});

it('MfiRunProvisioningCommand has correct signature name', function (): void {
    $command = $this->app->make(MfiRunProvisioningCommand::class);
    expect($command->getName())->toBe('mfi:run-provisioning');
});

it('MfiRunProvisioningCommand has correct description', function (): void {
    $command = $this->app->make(MfiRunProvisioningCommand::class);
    expect($command->getDescription())->not->toBeEmpty();
});
