<?php

declare(strict_types=1);

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Services\FieldDefinitions;
use App\Domain\ISO8583\Services\MessageCodec;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->codec = new MessageCodec(new FieldDefinitions());
});

it('encodes and decodes a simple authorization request', function (): void {
    $msg = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $msg->setField(2, '4111111111111111');
    $msg->setField(3, '000000');
    $msg->setField(4, '000000010000');
    $msg->setField(11, '123456');
    $msg->setField(41, 'TERM0001');
    $msg->setField(42, 'MERCH00000001');
    $msg->setField(49, '840');

    $encoded = $this->codec->encode($msg);
    $decoded = $this->codec->decode($encoded);

    expect($decoded->getMti())->toBe(MessageTypeIndicator::AUTH_REQUEST);
    expect($decoded->getField(2))->toBe('4111111111111111');
    expect($decoded->getField(4))->toBe('000000010000');
    expect($decoded->getField(49))->toBe('840');
});

it('handles LLVAR fields correctly', function (): void {
    $msg = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $msg->setField(2, '4111111111111111');

    $encoded = $this->codec->encode($msg);
    $decoded = $this->codec->decode($encoded);

    expect($decoded->getField(2))->toBe('4111111111111111');
});

it('preserves all fields through round trip', function (): void {
    $msg = new Iso8583Message(MessageTypeIndicator::FINANCIAL_REQUEST);
    $msg->setField(2, '5500000000000004');
    $msg->setField(3, '000000');
    $msg->setField(4, '000000005000');
    $msg->setField(11, '654321');
    $msg->setField(37, 'REF123456789');
    $msg->setField(41, 'TERM0002');
    $msg->setField(42, 'MERCH00000002');
    $msg->setField(49, '978');

    $decoded = $this->codec->decode($this->codec->encode($msg));

    expect($decoded->getField(2))->toBe('5500000000000004');
    expect($decoded->getField(37))->toBe('REF123456789');
    expect($decoded->getMti())->toBe(MessageTypeIndicator::FINANCIAL_REQUEST);
});
