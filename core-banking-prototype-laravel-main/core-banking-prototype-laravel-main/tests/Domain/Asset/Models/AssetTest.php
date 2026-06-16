<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;

describe('Asset Model', function () {
    it('uses correct table', function () {
        $asset = new Asset();
        expect($asset->getTable())->toBe('assets');
    });

    it('has correct primary key', function () {
        $asset = new Asset();
        expect($asset->getKeyName())->toBe('code');
    });

    it('does not use incrementing primary key', function () {
        $asset = new Asset();
        expect($asset->getIncrementing())->toBeFalse();
    });

    it('uses string key type', function () {
        $asset = new Asset();
        expect($asset->getKeyType())->toBe('string');
    });

    it('has correct fillable attributes', function () {
        $asset = new Asset();
        $fillable = $asset->getFillable();

        expect($fillable)->toContain('code');
        expect($fillable)->toContain('name');
        expect($fillable)->toContain('type');
        expect($fillable)->toContain('precision');
        expect($fillable)->toContain('is_active');
        expect($fillable)->toContain('metadata');
    });

    it('has correct casts', function () {
        $asset = new Asset();
        $casts = $asset->getCasts();

        expect($casts)->toHaveKey('is_active');
        expect($casts)->toHaveKey('metadata');
        expect($casts)->toHaveKey('precision');
    });

    it('can create asset with all attributes', function () {
        $asset = Asset::factory()->create([
            'code'      => 'TEST',
            'name'      => 'Test Currency',
            'type'      => 'fiat',
            'precision' => 2,
            'is_active' => true,
            'metadata'  => ['country' => 'Test'],
        ]);

        expect($asset->code)->toBe('TEST');
        expect($asset->name)->toBe('Test Currency');
        expect($asset->type)->toBe('fiat');
        expect($asset->precision)->toBe(2);
        expect($asset->is_active)->toBeTrue();
        expect($asset->metadata)->toBe(['country' => 'Test']);
    });

    it('has account balances relationship defined', function () {
        $asset = new Asset();
        expect((new ReflectionClass($asset))->hasMethod('accountBalances'))->toBeTrue();
    });

    it('has exchange rates from relationship defined', function () {
        $asset = new Asset();
        expect((new ReflectionClass($asset))->hasMethod('exchangeRatesFrom'))->toBeTrue();
    });

    it('has exchange rates to relationship defined', function () {
        $asset = new Asset();
        expect((new ReflectionClass($asset))->hasMethod('exchangeRatesTo'))->toBeTrue();
    });

    it('has active scope', function () {
        $asset = new Asset();
        expect((new ReflectionClass($asset))->hasMethod('scopeActive'))->toBeTrue();
    });

    it('has of type scope', function () {
        $asset = new Asset();
        expect((new ReflectionClass($asset))->hasMethod('scopeOfType'))->toBeTrue();
    });
});
