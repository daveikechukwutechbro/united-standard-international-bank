<?php

declare(strict_types=1);

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\Enums\ProcessingCode;
use App\Domain\ISO8583\Enums\ResponseCode;

it('identifies request MTIs', function (): void {
    expect(MessageTypeIndicator::AUTH_REQUEST->isRequest())->toBeTrue();
    expect(MessageTypeIndicator::AUTH_RESPONSE->isRequest())->toBeFalse();
    expect(MessageTypeIndicator::REVERSAL_REQUEST->isRequest())->toBeTrue();
});

it('maps request to response MTI', function (): void {
    expect(MessageTypeIndicator::AUTH_REQUEST->responseType())->toBe(MessageTypeIndicator::AUTH_RESPONSE);
    expect(MessageTypeIndicator::REVERSAL_REQUEST->responseType())->toBe(MessageTypeIndicator::REVERSAL_RESPONSE);
    expect(MessageTypeIndicator::AUTH_RESPONSE->responseType())->toBeNull();
});

it('has correct processing code values', function (): void {
    expect(ProcessingCode::PURCHASE->value)->toBe('00');
    expect(ProcessingCode::REFUND->value)->toBe('20');
    expect(ProcessingCode::BALANCE_INQUIRY->label())->toBe('Balance Inquiry');
});

it('identifies approved response code', function (): void {
    expect(ResponseCode::APPROVED->isApproved())->toBeTrue();
    expect(ResponseCode::DECLINED->isApproved())->toBeFalse();
    expect(ResponseCode::INSUFFICIENT_FUNDS->isApproved())->toBeFalse();
});
