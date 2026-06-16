<?php

declare(strict_types=1);

use App\Domain\X402\DataObjects\MonetizedRouteConfig;
use App\Domain\X402\DataObjects\PaymentRequired;
use App\Domain\X402\Services\X402PricingService;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->service = new X402PricingService();
});

test('converts USD to atomic units correctly', function () {
    expect($this->service->usdToAtomicUnits('1.00'))->toBe('1000000');
    expect($this->service->usdToAtomicUnits('0.001'))->toBe('1000');
    expect($this->service->usdToAtomicUnits('0.01'))->toBe('10000');
    expect($this->service->usdToAtomicUnits('100'))->toBe('100000000');
    expect($this->service->usdToAtomicUnits('0'))->toBe('0');
});

test('converts atomic units to USD correctly', function () {
    expect($this->service->atomicToUsd('1000000'))->toBe(1.0);
    expect($this->service->atomicToUsd('1000'))->toBe(0.001);
    expect($this->service->atomicToUsd('10000'))->toBe(0.01);
    expect($this->service->atomicToUsd('0'))->toBe(0.0);
});

test('builds PaymentRequired from route config', function () {
    config(['x402.enabled' => true]);
    config(['x402.server.pay_to' => '0x1234567890abcdef1234567890abcdef12345678']);
    config(['x402.server.max_timeout_seconds' => 60]);
    config(['x402.version' => 2]);
    config(['x402.assets.eip155:8453.USDC' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913']);

    $routeConfig = new MonetizedRouteConfig(
        method: 'GET',
        path: '/api/v1/premium/data',
        price: '0.01',
        network: 'eip155:8453',
        asset: 'USDC',
        scheme: 'exact',
        description: 'Premium data',
        mimeType: 'application/json',
        extra: [],
    );

    $request = Request::create('/api/v1/premium/data', 'GET');
    $result = $this->service->buildPaymentRequired($request, $routeConfig);

    expect($result)->toBeInstanceOf(PaymentRequired::class);
    expect($result->x402Version)->toBe(2);
    expect($result->accepts)->toHaveCount(1);
    expect($result->accepts[0]->amount)->toBe('10000'); // 0.01 USD = 10000 atomic
    expect($result->accepts[0]->scheme)->toBe('exact');
    expect($result->accepts[0]->network)->toBe('eip155:8453');
    expect($result->resource->description)->toBe('Premium data');
});

test('getRouteConfig returns null when disabled', function () {
    config(['x402.enabled' => false]);

    $request = Request::create('/api/v1/data', 'GET');
    $result = $this->service->getRouteConfig($request);

    expect($result)->toBeNull();
});
