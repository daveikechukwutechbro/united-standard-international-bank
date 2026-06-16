<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Domain\ISO20022\Services\MessageParser;
use App\Domain\ISO20022\Services\MessageRegistry;
use App\Domain\ISO20022\ValueObjects\Pacs008;
use App\Domain\ISO20022\ValueObjects\Pain001;

// ── Helpers ────────────────────────────────────────────────────────────────

/** @return array<string, mixed> */
function makePain001Data(): array
{
    return [
        'message_id'             => 'MSG-PARSER-001',
        'creation_date_time'     => '2024-03-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '250.00',
        'initiating_party_name'  => 'Test Corp',
        'debtor_name'            => 'Alice Smith',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'payment_method'         => 'TRF',
        'transactions'           => [
            [
                'end_to_end_id' => 'E2E-PARSER-001',
                'amount'        => '250.00',
                'currency'      => 'EUR',
                'creditor_name' => 'Bob Jones',
                'creditor_iban' => 'DE89370400440532013000',
            ],
        ],
    ];
}

/** @return array<string, mixed> */
function makePacs008Data(): array
{
    return [
        'message_id'             => 'PACS-PARSER-001',
        'creation_date_time'     => '2024-03-15T11:00:00',
        'number_of_transactions' => 1,
        'settlement_method'      => 'CLRG',
        'instructing_agent_bic'  => 'NWBKGB2L',
        'instructed_agent_bic'   => 'DEUTDEDB',
        'end_to_end_id'          => 'E2E-PACS-001',
        'uetr'                   => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'amount'                 => '500.00',
        'currency'               => 'EUR',
        'charge_bearer'          => 'SLEV',
        'debtor_name'            => 'Charlie Brown',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'creditor_name'          => 'Diana Prince',
        'creditor_iban'          => 'DE89370400440532013000',
    ];
}

function makeParser(): MessageParser
{
    return new MessageParser(new MessageRegistry());
}

// ── Pain001 round-trip ─────────────────────────────────────────────────────

it('round-trips Pain001: generate XML then parse back with matching fields', function (): void {
    $data = makePain001Data();
    $dto = Pain001::fromArray($data);
    $xml = $dto->toXml();

    $parser = makeParser();
    $parsed = $parser->parseXml($xml);
    assert($parsed instanceof Pain001);

    expect($parsed)->toBeInstanceOf(Pain001::class)
        ->and($parsed->messageId)->toBe('MSG-PARSER-001')
        ->and($parsed->numberOfTransactions)->toBe(1)
        ->and($parsed->controlSum)->toBe('250.00')
        ->and($parsed->initiatingPartyName)->toBe('Test Corp')
        ->and($parsed->debtorName)->toBe('Alice Smith')
        ->and($parsed->debtorIban)->toBe('GB29NWBK60161331926819')
        ->and($parsed->debtorBic)->toBe('NWBKGB2L')
        ->and($parsed->paymentMethod)->toBe('TRF')
        ->and($parsed->transactions)->toHaveCount(1)
        ->and($parsed->transactions[0]['end_to_end_id'])->toBe('E2E-PARSER-001')
        ->and($parsed->transactions[0]['amount'])->toBe('250.00')
        ->and($parsed->transactions[0]['currency'])->toBe('EUR')
        ->and($parsed->transactions[0]['creditor_name'])->toBe('Bob Jones')
        ->and($parsed->transactions[0]['creditor_iban'])->toBe('DE89370400440532013000');
});

// ── Pacs008 round-trip ─────────────────────────────────────────────────────

it('round-trips Pacs008: generate XML then parse back with matching fields', function (): void {
    $data = makePacs008Data();
    $dto = Pacs008::fromArray($data);
    $xml = $dto->toXml();

    $parser = makeParser();
    $parsed = $parser->parseXml($xml);
    assert($parsed instanceof Pacs008);

    expect($parsed)->toBeInstanceOf(Pacs008::class)
        ->and($parsed->messageId)->toBe('PACS-PARSER-001')
        ->and($parsed->numberOfTransactions)->toBe(1)
        ->and($parsed->settlementMethod)->toBe('CLRG')
        ->and($parsed->instructingAgentBic)->toBe('NWBKGB2L')
        ->and($parsed->instructedAgentBic)->toBe('DEUTDEDB')
        ->and($parsed->endToEndId)->toBe('E2E-PACS-001')
        ->and($parsed->uetr)->toBe('f47ac10b-58cc-4372-a567-0e02b2c3d479')
        ->and($parsed->amount)->toBe('500.00')
        ->and($parsed->currency)->toBe('EUR')
        ->and($parsed->debtorName)->toBe('Charlie Brown')
        ->and($parsed->debtorIban)->toBe('GB29NWBK60161331926819')
        ->and($parsed->creditorName)->toBe('Diana Prince')
        ->and($parsed->creditorIban)->toBe('DE89370400440532013000');
});

// ── Unrecognized namespace ─────────────────────────────────────────────────

it('throws RuntimeException for unrecognized namespace', function (): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Document xmlns="urn:unknown:namespace">'
        . '<SomeMessage><Header><MsgId>UNKNOWN-001</MsgId></Header></SomeMessage>'
        . '</Document>';

    $parser = makeParser();

    expect(fn () => $parser->parseXml($xml))
        ->toThrow(RuntimeException::class, 'Unrecognized ISO 20022 namespace');
});

// ── parseArray ─────────────────────────────────────────────────────────────

it('parseArray constructs correct DTO type from message type and data', function (): void {
    $data = makePain001Data();
    $parser = makeParser();

    $dto = $parser->parseArray('pain.001', $data);

    expect($dto)->toBeInstanceOf(Pain001::class);
    assert($dto instanceof Pain001);
    expect($dto->messageId)->toBe('MSG-PARSER-001')
        ->and($dto->initiatingPartyName)->toBe('Test Corp');
});

it('parseArray throws RuntimeException for unsupported message type', function (): void {
    $parser = makeParser();

    expect(fn () => $parser->parseArray('unknown.999', []))
        ->toThrow(RuntimeException::class, 'Unsupported message type');
});
