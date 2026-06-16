<?php

declare(strict_types=1);

use App\Domain\X402\DataObjects\MonetizedRouteConfig;
use App\Domain\X402\DataObjects\PaymentRequired;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\ResourceInfo;
use App\Domain\X402\DataObjects\SettleResponse;
use App\Domain\X402\DataObjects\VerifyResponse;

test('ResourceInfo can be created and serialized', function () {
    $info = new ResourceInfo(
        url: 'https://api.example.com/data',
        description: 'Premium data endpoint',
        mimeType: 'application/json',
    );

    expect($info->url)->toBe('https://api.example.com/data');
    expect($info->description)->toBe('Premium data endpoint');
    expect($info->mimeType)->toBe('application/json');

    $array = $info->toArray();
    expect($array)->toHaveKeys(['url', 'description', 'mimeType']);

    $fromArray = ResourceInfo::fromArray($array);
    expect($fromArray->url)->toBe($info->url);
});

test('PaymentRequirements can be created and serialized', function () {
    $req = new PaymentRequirements(
        scheme: 'exact',
        network: 'eip155:8453',
        asset: '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
        amount: '1000',
        payTo: '0x1234567890abcdef1234567890abcdef12345678',
        maxTimeoutSeconds: 60,
        extra: ['name' => 'USD Coin', 'version' => '2'],
    );

    expect($req->scheme)->toBe('exact');
    expect($req->network)->toBe('eip155:8453');
    expect($req->amount)->toBe('1000');

    $array = $req->toArray();
    expect($array)->toHaveKeys(['scheme', 'network', 'asset', 'amount', 'payTo', 'maxTimeoutSeconds', 'extra']);

    $fromArray = PaymentRequirements::fromArray($array);
    expect($fromArray->scheme)->toBe('exact');
    expect($fromArray->network)->toBe('eip155:8453');
});

test('PaymentRequired can be created with Base64 encoding', function () {
    $resource = new ResourceInfo(
        url: 'https://api.example.com/data',
        description: 'Test',
        mimeType: 'application/json',
    );

    $requirements = new PaymentRequirements(
        scheme: 'exact',
        network: 'eip155:8453',
        asset: '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
        amount: '1000',
        payTo: '0x1234567890abcdef1234567890abcdef12345678',
        maxTimeoutSeconds: 60,
        extra: [],
    );

    $pr = new PaymentRequired(
        x402Version: 2,
        resource: $resource,
        accepts: [$requirements],
    );

    expect($pr->x402Version)->toBe(2);
    expect($pr->accepts)->toHaveCount(1);

    $base64 = $pr->toBase64();
    expect($base64)->toBeString();

    $decoded = PaymentRequired::fromBase64($base64);
    expect($decoded->x402Version)->toBe(2);
    expect($decoded->resource->url)->toBe('https://api.example.com/data');
});

test('VerifyResponse can be created from array', function () {
    $response = VerifyResponse::fromArray([
        'isValid' => true,
        'payer'   => '0x1234567890abcdef1234567890abcdef12345678',
    ]);

    expect($response->isValid)->toBeTrue();
    expect($response->payer)->toBe('0x1234567890abcdef1234567890abcdef12345678');
});

test('VerifyResponse handles invalid state', function () {
    $response = VerifyResponse::fromArray([
        'isValid'        => false,
        'invalidReason'  => 'insufficient_funds',
        'invalidMessage' => 'Not enough USDC',
    ]);

    expect($response->isValid)->toBeFalse();
    expect($response->invalidReason)->toBe('insufficient_funds');
});

test('SettleResponse can be created and encoded to Base64', function () {
    $response = new SettleResponse(
        success: true,
        payer: '0x1234567890abcdef1234567890abcdef12345678',
        transaction: '0xdeadbeef',
        network: 'eip155:8453',
    );

    expect($response->success)->toBeTrue();
    expect($response->payer)->toBe('0x1234567890abcdef1234567890abcdef12345678');

    $base64 = $response->toBase64();
    expect($base64)->toBeString();
});

test('MonetizedRouteConfig is readonly', function () {
    $config = new MonetizedRouteConfig(
        method: 'GET',
        path: '/api/v1/data',
        price: '0.001',
        network: 'eip155:8453',
        asset: 'USDC',
        scheme: 'exact',
        description: 'Test endpoint',
        mimeType: 'application/json',
        extra: [],
    );

    expect($config->method)->toBe('GET');
    expect($config->price)->toBe('0.001');
    expect($config->network)->toBe('eip155:8453');
});
