<?php

declare(strict_types=1);

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\ValueObjects\Bitmap;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;

it('sets and checks fields', function (): void {
    $bitmap = new Bitmap();
    $bitmap->setField(2)->setField(3)->setField(4);

    expect($bitmap->hasField(2))->toBeTrue();
    expect($bitmap->hasField(3))->toBeTrue();
    expect($bitmap->hasField(5))->toBeFalse();
});

it('encodes primary bitmap to hex', function (): void {
    $bitmap = new Bitmap();
    $bitmap->setField(2)->setField(3)->setField(4);

    $hex = $bitmap->encode();
    expect(strlen($hex))->toBe(16);
});

it('round-trips through encode/decode', function (): void {
    $original = new Bitmap();
    $original->setField(2)->setField(11)->setField(39)->setField(41);

    $decoded = Bitmap::decode($original->encode());
    expect($decoded->presentFields())->toBe($original->presentFields());
});

it('detects secondary bitmap need for fields > 64', function (): void {
    $bitmap = new Bitmap();
    $bitmap->setField(2)->setField(70);

    expect($bitmap->hasSecondaryBitmap())->toBeTrue();
    expect(strlen($bitmap->encode()))->toBe(32);
});

it('rejects invalid field numbers', function (): void {
    $bitmap = new Bitmap();
    $bitmap->setField(0);
})->throws(InvalidArgumentException::class);

it('creates ISO 8583 message with fields', function (): void {
    $msg = new Iso8583Message(MessageTypeIndicator::AUTH_REQUEST);
    $msg->setField(2, '4111111111111111');
    $msg->setField(4, '000000010000');
    $msg->setField(49, '840');

    expect($msg->getMti())->toBe(MessageTypeIndicator::AUTH_REQUEST);
    expect($msg->getField(2))->toBe('4111111111111111');
    expect($msg->getField(4))->toBe('000000010000');
    expect($msg->getField(99))->toBeNull();
    expect($msg->presentFields())->toBe([2, 4, 49]);
});
