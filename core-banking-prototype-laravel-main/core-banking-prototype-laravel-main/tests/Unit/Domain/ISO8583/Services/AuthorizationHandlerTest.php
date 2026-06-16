<?php

declare(strict_types=1);

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Enums\ResponseCode;
use App\Domain\ISO8583\Services\AuthorizationHandler;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->handler = new AuthorizationHandler();
});

it('approves valid authorization request', function (): void {
    $request = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $request->setField(2, '4111111111111111');
    $request->setField(3, '000000');
    $request->setField(4, '000000010000');
    $request->setField(11, '123456');
    $request->setField(41, 'TERM0001');
    $request->setField(49, '840');

    $response = $this->handler->handleRequest($request);

    expect($response->getMti())->toBe(MessageTypeIndicator::AUTH_RESPONSE);
    expect($response->getField(39))->toBe(ResponseCode::APPROVED->value);
    expect($response->getField(38))->not->toBeNull(); // auth code generated
    expect($response->getField(2))->toBe('4111111111111111'); // echoed
});

it('rejects request with missing PAN', function (): void {
    $request = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $request->setField(4, '000000010000');

    $response = $this->handler->handleRequest($request);
    expect($response->getField(39))->toBe(ResponseCode::INVALID_TRANSACTION->value);
});

it('rejects zero amount', function (): void {
    $request = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $request->setField(2, '4111111111111111');
    $request->setField(4, '000000000000');

    $response = $this->handler->handleRequest($request);
    expect($response->getField(39))->toBe(ResponseCode::INVALID_TRANSACTION->value);
});
