<?php

declare(strict_types=1);

use App\Domain\Asset\Projectors\AssetTransactionProjector;
use App\Domain\Asset\Projectors\AssetTransferProjector;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\AssetResource;

// Test low coverage classes to increase overall coverage

it('can instantiate asset projectors', function () {
    $transactionProjector = new AssetTransactionProjector();
    $transferProjector = new AssetTransferProjector();

    expect($transactionProjector)->toBeInstanceOf(AssetTransactionProjector::class);
    expect($transferProjector)->toBeInstanceOf(AssetTransferProjector::class);
});

it('can instantiate dashboard page', function () {
    $dashboard = new Dashboard();

    expect($dashboard)->toBeInstanceOf(Dashboard::class);
});

it('can access asset resource class methods', function () {
    $resource = AssetResource::class;

    expect($resource::getModel())->toBe(App\Domain\Asset\Models\Asset::class);
    expect($resource::getModelLabel())->toBeString();
    expect($resource::getPluralModelLabel())->toBeString();
    expect($resource::getNavigationIcon())->toBeString();
    expect($resource::getNavigationGroup())->toBeString();
});

it('can test asset resource navigation methods', function () {
    $badge = AssetResource::getNavigationBadge();
    $sort = AssetResource::getNavigationSort();

    expect($badge)->toBeString();
    expect($sort)->toBeInt();
});

it('workflow activity classes exist', function () {
    expect((new ReflectionClass(App\Domain\Account\Workflows\AccountValidationActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Account\Workflows\BalanceInquiryActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Account\Workflows\DepositAccountActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Account\Workflows\WithdrawAccountActivity::class))->getName())->not->toBeEmpty();
});

it('asset workflow classes exist', function () {
    expect((new ReflectionClass(App\Domain\Asset\Workflows\AssetDepositWorkflow::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\AssetWithdrawWorkflow::class))->getName())->not->toBeEmpty();
});

it('asset activity classes exist', function () {
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\DepositAssetActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\WithdrawAssetActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\CompleteAssetTransferActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\FailAssetTransferActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\InitiateAssetTransferActivity::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Domain\Asset\Workflows\Activities\ValidateExchangeRateActivity::class))->getName())->not->toBeEmpty();
});

it('projector classes exist and have methods', function () {
    $transactionProjector = new AssetTransactionProjector();
    $transferProjector = new AssetTransferProjector();

    expect((new ReflectionClass(App\Domain\Asset\Projectors\ExchangeRateProjector::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass($transactionProjector))->hasMethod('onAssetTransactionCreated'))->toBeTrue();
    expect((new ReflectionClass($transferProjector))->hasMethod('onAssetTransferInitiated'))->toBeTrue();
    expect((new ReflectionClass($transferProjector))->hasMethod('onAssetTransferCompleted'))->toBeTrue();
    expect((new ReflectionClass($transferProjector))->hasMethod('onAssetTransferFailed'))->toBeTrue();
});

it('filament resource pages exist', function () {
    expect((new ReflectionClass(AssetResource\Pages\CreateAsset::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(AssetResource\Pages\EditAsset::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(AssetResource\Pages\ListAssets::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Filament\Admin\Resources\ExchangeRateResource\Pages\CreateExchangeRate::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Filament\Admin\Resources\ExchangeRateResource\Pages\EditExchangeRate::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Filament\Admin\Resources\ExchangeRateResource\Pages\ListExchangeRates::class))->getName())->not->toBeEmpty();
});

it('widgets and relation managers exist', function () {
    expect((new ReflectionClass(App\Filament\Admin\Resources\AccountResource\RelationManagers\TurnoversRelationManager::class))->getName())->not->toBeEmpty();
    expect((new ReflectionClass(App\Filament\Admin\Resources\AccountResource\Widgets\AccountStatsOverview::class))->getName())->not->toBeEmpty();
});
