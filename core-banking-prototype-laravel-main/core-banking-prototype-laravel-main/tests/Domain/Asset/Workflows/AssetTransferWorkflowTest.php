<?php

declare(strict_types=1);

use App\Domain\Asset\Workflows\Activities\CompleteAssetTransferActivity;
use App\Domain\Asset\Workflows\Activities\FailAssetTransferActivity;
use App\Domain\Asset\Workflows\Activities\InitiateAssetTransferActivity;
use App\Domain\Asset\Workflows\Activities\ValidateExchangeRateActivity;
use App\Domain\Asset\Workflows\AssetTransferWorkflow;

beforeEach(function () {
    // Assets are already seeded in migrations, no need to create duplicates
});

it('can create asset transfer workflow class', function () {
    // Test that the class exists and is properly structured
    expect((new ReflectionClass(AssetTransferWorkflow::class))->getName())->not->toBeEmpty();
    expect(is_subclass_of(AssetTransferWorkflow::class, Workflow\Workflow::class))->toBeTrue();
});

it('has execute method with correct signature', function () {
    $reflection = new ReflectionClass(AssetTransferWorkflow::class);
    $method = $reflection->getMethod('execute');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(5); // 4 required + 1 optional
    expect($method->getReturnType()?->getName())->toBe('Generator');
});

it('validates workflow activities exist', function () {
    // Test that all required activities exist
    expect((new ReflectionClass(InitiateAssetTransferActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(ValidateExchangeRateActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(CompleteAssetTransferActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(FailAssetTransferActivity::class))->getName())->not->toBeEmpty();
});
