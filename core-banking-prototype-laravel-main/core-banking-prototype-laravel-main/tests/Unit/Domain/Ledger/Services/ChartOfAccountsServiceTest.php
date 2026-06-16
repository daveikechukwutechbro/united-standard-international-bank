<?php

declare(strict_types=1);

use App\Domain\Ledger\Enums\AccountType;
use App\Domain\Ledger\Services\ChartOfAccountsService;

describe('ChartOfAccountsService', function (): void {
    it('exists as a class', function (): void {
        expect(class_exists(ChartOfAccountsService::class))->toBeTrue();
    });

    it('is declared as final', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('declares the createAccount method with 6 parameters (last 3 optional)', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('createAccount'))->toBeTrue();

        $method = $reflection->getMethod('createAccount');
        $params = $method->getParameters();

        expect($params)->toHaveCount(6);
        expect($params[0]->getName())->toBe('code');
        expect($params[1]->getName())->toBe('name');
        expect($params[2]->getName())->toBe('type');
        expect($params[3]->getName())->toBe('parentCode');
        expect($params[4]->getName())->toBe('currency');
        expect($params[5]->getName())->toBe('description');

        // First 3 are required
        expect($params[0]->isOptional())->toBeFalse();
        expect($params[1]->isOptional())->toBeFalse();
        expect($params[2]->isOptional())->toBeFalse();

        // Last 3 have defaults
        expect($params[3]->isOptional())->toBeTrue();
        expect($params[4]->isOptional())->toBeTrue();
        expect($params[5]->isOptional())->toBeTrue();
    });

    it('declares the getAll method', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('getAll'))->toBeTrue();

        $method = $reflection->getMethod('getAll');
        expect($method->getParameters())->toHaveCount(0);
    });

    it('declares the getByType method with an AccountType parameter', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('getByType'))->toBeTrue();

        $method = $reflection->getMethod('getByType');
        $params = $method->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('type');

        $type = $params[0]->getType();
        assert($type instanceof ReflectionNamedType);
        expect($type->getName())->toBe(AccountType::class);
    });

    it('declares the getRootAccounts method', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('getRootAccounts'))->toBeTrue();

        $method = $reflection->getMethod('getRootAccounts');
        expect($method->getParameters())->toHaveCount(0);
    });

    it('declares the deactivateAccount method', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('deactivateAccount'))->toBeTrue();

        $method = $reflection->getMethod('deactivateAccount');
        $params = $method->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('code');
    });

    it('declares the seedDefaultAccounts method', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        expect($reflection->hasMethod('seedDefaultAccounts'))->toBeTrue();

        $method = $reflection->getMethod('seedDefaultAccounts');
        expect($method->getParameters())->toHaveCount(0);
    });

    it('seedDefaultAccounts list contains exactly 21 default accounts', function (): void {
        // Verify the method body seeds exactly 21 accounts by inspecting the source
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        $fileName = $reflection->getFileName();
        assert($fileName !== false);
        $source = file_get_contents($fileName);
        assert($source !== false);

        // Each account entry is a 4-element array starting with a quoted account code
        // Count occurrences like ['1000', or ['2000', etc. inside the $accounts array
        $matches = [];
        preg_match_all("/\['[0-9]{4}',/", $source, $matches);
        expect(count($matches[0]))->toBe(21);
    });

    it('seedDefaultAccounts covers all five account types', function (): void {
        $reflection = new ReflectionClass(ChartOfAccountsService::class);
        $fileName = $reflection->getFileName();
        assert($fileName !== false);
        $source = file_get_contents($fileName);
        assert($source !== false);

        expect($source)->toContain('AccountType::ASSET');
        expect($source)->toContain('AccountType::LIABILITY');
        expect($source)->toContain('AccountType::EQUITY');
        expect($source)->toContain('AccountType::REVENUE');
        expect($source)->toContain('AccountType::EXPENSE');
    });
});
