<?php

declare(strict_types=1);

use App\Domain\X402\DataObjects\PaymentRequired;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\ResourceInfo;
use App\Domain\X402\DataObjects\SettleResponse;
use App\Domain\X402\Exceptions\X402InvalidPayloadException;
use App\Domain\X402\Services\X402HeaderCodecService;

beforeEach(function () {
    $this->codec = new X402HeaderCodecService();
});

test('encodes PaymentRequired to Base64', function () {
    $resource = new ResourceInfo(
        url: 'https://api.example.com/endpoint',
        description: 'Test endpoint',
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

    $encoded = $this->codec->encodePaymentRequired($pr);
    expect($encoded)->toBeString();

    // Verify it's valid base64
    $decoded = base64_decode($encoded, true);
    expect($decoded)->not->toBeFalse();

    // Verify it's valid JSON
    $json = json_decode((string) $decoded, true);
    expect($json)->toBeArray();
    expect($json)->toHaveKey('x402Version');
});

test('decodes PaymentRequired from Base64 header', function () {
    $resource = new ResourceInfo(
        url: 'https://api.example.com/endpoint',
        description: 'Test',
        mimeType: 'application/json',
    );

    $requirements = new PaymentRequirements(
        scheme: 'exact',
        network: 'eip155:8453',
        asset: '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
        amount: '500000',
        payTo: '0x1234567890abcdef1234567890abcdef12345678',
        maxTimeoutSeconds: 30,
        extra: [],
    );

    $original = new PaymentRequired(
        x402Version: 2,
        resource: $resource,
        accepts: [$requirements],
    );

    $encoded = $this->codec->encodePaymentRequired($original);
    $decoded = $this->codec->decodePaymentRequired($encoded);

    expect($decoded->x402Version)->toBe(2);
    expect($decoded->resource->url)->toBe('https://api.example.com/endpoint');
    expect($decoded->accepts)->toHaveCount(1);
    expect($decoded->accepts[0]->amount)->toBe('500000');
});

test('encodes SettleResponse to Base64', function () {
    $response = new SettleResponse(
        success: true,
        payer: '0x1234567890abcdef1234567890abcdef12345678',
        transaction: '0xdeadbeef',
        network: 'eip155:8453',
    );

    $encoded = $this->codec->encodeSettleResponse($response);
    expect($encoded)->toBeString();

    $decoded = base64_decode($encoded, true);
    expect($decoded)->not->toBeFalse();
});

/** @phpstan-ignore-next-line method.notFound */
test('throws on invalid base64 input', function () {
    $this->codec->decodePaymentRequired('not-valid-base64!!!');
})->throws(X402InvalidPayloadException::class);

/** @phpstan-ignore-next-line method.notFound */
test('throws on invalid JSON after base64 decode', function () {
    $encoded = base64_encode('not valid json');
    $this->codec->decodePaymentRequired($encoded);
})->throws(X402InvalidPayloadException::class);
