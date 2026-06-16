<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Domain\ISO20022\Enums\MessageFamily;
use App\Domain\ISO20022\Enums\TransactionStatus;
use App\Domain\ISO20022\ValueObjects\BusinessApplicationHeader;

it('has correct message family values', function (): void {
    expect(MessageFamily::PAIN->value)->toBe('pain');
    expect(MessageFamily::PACS->value)->toBe('pacs');
    expect(MessageFamily::CAMT->value)->toBe('camt');
});

it('returns human-readable labels', function (): void {
    expect(MessageFamily::PAIN->label())->toBe('Payment Initiation');
    expect(MessageFamily::PACS->label())->toBe('Payments Clearing and Settlement');
});

it('identifies terminal transaction statuses', function (): void {
    expect(TransactionStatus::ACSC->isTerminal())->toBeTrue();
    expect(TransactionStatus::RJCT->isTerminal())->toBeTrue();
    expect(TransactionStatus::CANC->isTerminal())->toBeTrue();
    expect(TransactionStatus::PDNG->isTerminal())->toBeFalse();
    expect(TransactionStatus::ACSP->isTerminal())->toBeFalse();
});

it('identifies successful transaction statuses', function (): void {
    expect(TransactionStatus::ACSC->isSuccessful())->toBeTrue();
    expect(TransactionStatus::RJCT->isSuccessful())->toBeFalse();
});

it('creates business application header with UETR', function (): void {
    $bah = BusinessApplicationHeader::create(
        messageDefinitionId: 'pacs.008.001.08',
        from: 'BANKUS33',
        to: 'BANKGB2L',
    );

    expect($bah->businessMessageId)->not->toBeEmpty();
    expect($bah->messageDefinitionId)->toBe('pacs.008.001.08');
    expect($bah->from)->toBe('BANKUS33');
    expect($bah->to)->toBe('BANKGB2L');
    expect($bah->family())->toBe(MessageFamily::PACS);
});

it('converts header to array', function (): void {
    $bah = BusinessApplicationHeader::create(
        messageDefinitionId: 'pain.001.001.09',
        from: 'SENDER',
        to: 'RECEIVER',
    );

    $array = $bah->toArray();
    expect($array)->toHaveKeys([
        'business_message_id',
        'message_definition_id',
        'from',
        'to',
        'creation_date',
        'uetr',
    ]);
});
