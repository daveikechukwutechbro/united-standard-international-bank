<?php

declare(strict_types=1);

use App\Domain\Account\Models\Account;
use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Stablecoin\Contracts\StablecoinIssuanceServiceInterface;
use App\Domain\Stablecoin\Models\Stablecoin;
use App\Domain\Stablecoin\Models\StablecoinCollateralPosition;
use App\Domain\Stablecoin\Services\CollateralService;
use App\Domain\Stablecoin\Services\StablecoinIssuanceService;
use App\Domain\Wallet\Services\WalletService;

uses(Tests\TestCase::class);

describe('StablecoinIssuanceService', function () {
    beforeEach(function () {
        $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $this->collateralService = Mockery::mock(CollateralService::class);
        $this->walletService = Mockery::mock(WalletService::class);

        $this->service = new StablecoinIssuanceService(
            $this->exchangeRateService,
            $this->collateralService,
            $this->walletService
        );
    });

    it('implements StablecoinIssuanceServiceInterface', function () {
        expect($this->service)->toBeInstanceOf(StablecoinIssuanceServiceInterface::class);
    });

    it('can be constructed with exchange rate, collateral, and wallet services', function () {
        expect($this->service)->toBeInstanceOf(StablecoinIssuanceService::class);
    });

    describe('mint', function () {
        it('requires an account, stablecoin code, collateral asset code, collateral amount, and mint amount', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'mint');

            expect($method->getNumberOfParameters())->toBe(5);
            expect($method->isPublic())->toBeTrue();

            $params = $method->getParameters();
            expect($params[0]->getType()->getName())->toBe(Account::class);
            expect($params[1]->getType()->getName())->toBe('string');
            expect($params[2]->getType()->getName())->toBe('string');
            expect($params[3]->getType()->getName())->toBe('int');
            expect($params[4]->getType()->getName())->toBe('int');
        });

        it('returns a StablecoinCollateralPosition', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'mint');

            expect($method->getReturnType()->getName())->toBe(StablecoinCollateralPosition::class);
        });
    });

    describe('burn', function () {
        it('requires an account, stablecoin code, and burn amount with optional collateral release', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'burn');

            expect($method->getNumberOfParameters())->toBe(4);
            expect($method->isPublic())->toBeTrue();

            $params = $method->getParameters();
            expect($params[0]->getType()->getName())->toBe(Account::class);
            expect($params[1]->getType()->getName())->toBe('string');
            expect($params[2]->getType()->getName())->toBe('int');
            // Fourth parameter (collateralReleaseAmount) is optional
            expect($params[3]->isOptional())->toBeTrue();
        });

        it('returns a StablecoinCollateralPosition', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'burn');

            expect($method->getReturnType()->getName())->toBe(StablecoinCollateralPosition::class);
        });
    });

    describe('addCollateral', function () {
        it('is a public method for adding collateral to existing positions', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'addCollateral');

            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('validateCollateralSufficiency', function () {
        it('is a private validation method that enforces collateral ratios', function () {
            $method = new ReflectionMethod(StablecoinIssuanceService::class, 'validateCollateralSufficiency');

            expect($method->isPrivate())->toBeTrue();

            $params = $method->getParameters();
            expect($params[0]->getType()->getName())->toBe(Stablecoin::class);
            expect($params[1]->getType()->getName())->toBe('string'); // collateralAssetCode
            expect($params[2]->getType()->getName())->toBe('int');    // collateralAmount
            expect($params[3]->getType()->getName())->toBe('int');    // mintAmount
        });
    });
});
