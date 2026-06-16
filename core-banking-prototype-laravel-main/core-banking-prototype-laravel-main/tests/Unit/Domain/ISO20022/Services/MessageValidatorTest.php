<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Domain\ISO20022\Services\MessageRegistry;
use App\Domain\ISO20022\Services\MessageValidator;
use App\Domain\ISO20022\ValueObjects\Pain001;

// ── Helpers ────────────────────────────────────────────────────────────────

function makeValidator(): MessageValidator
{
    return new MessageValidator(new MessageRegistry());
}

function makeValidPain001Xml(): string
{
    $dto = Pain001::fromArray([
        'message_id'             => 'MSG-VALID-001',
        'creation_date_time'     => '2024-03-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '100.00',
        'initiating_party_name'  => 'Test Corp',
        'debtor_name'            => 'Alice',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'payment_method'         => 'TRF',
        'transactions'           => [
            [
                'end_to_end_id' => 'E2E-001',
                'amount'        => '100.00',
                'currency'      => 'EUR',
                'creditor_name' => 'Bob',
                'creditor_iban' => 'DE89370400440532013000',
            ],
        ],
    ]);

    return $dto->toXml();
}

// ── Valid Pain001 ──────────────────────────────────────────────────────────

it('validates a well-formed Pain001 XML as valid', function (): void {
    config(['iso20022.enabled_families' => ['pain', 'pacs', 'camt']]);
    config(['iso20022.max_message_size_kb' => 512]);

    $result = makeValidator()->validate(makeValidPain001Xml());

    expect($result['valid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

// ── Malformed XML ──────────────────────────────────────────────────────────

it('fails validation for malformed XML', function (): void {
    $result = makeValidator()->validate('<not valid xml <<< broken');

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->not()->toBeEmpty();
});

it('fails validation for unclosed XML tag', function (): void {
    $result = makeValidator()->validate('<Document><Unclosed></Document>');

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->not()->toBeEmpty();
});

// ── Unrecognized namespace ─────────────────────────────────────────────────

it('fails validation for unrecognized ISO 20022 namespace', function (): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Document xmlns="urn:unknown:xsd:fake.001.001.01">'
        . '<SomeMsg><GrpHdr><MsgId>TEST</MsgId></GrpHdr></SomeMsg>'
        . '</Document>';

    $result = makeValidator()->validate($xml);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain('Unrecognized ISO 20022 namespace');
});

// ── Disabled message family ────────────────────────────────────────────────

it('fails validation when message family is not in enabled_families', function (): void {
    config(['iso20022.enabled_families' => ['camt']]);
    config(['iso20022.max_message_size_kb' => 512]);

    $result = makeValidator()->validate(makeValidPain001Xml());

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain("Message family 'pain' is not enabled");
});

// ── Message size ───────────────────────────────────────────────────────────

it('fails validation when message exceeds max size', function (): void {
    config(['iso20022.enabled_families' => ['pain', 'pacs', 'camt']]);
    config(['iso20022.max_message_size_kb' => 1]);

    // Build a large message by appending padding inside a valid document
    $padding = str_repeat('X', 2048);
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.09">'
        . '<CstmrCdtTrfInitn>'
        . '<GrpHdr><MsgId>MSG-BIG</MsgId><CreDtTm>2024-01-01T00:00:00</CreDtTm>'
        . '<NbOfTxs>1</NbOfTxs><CtrlSum>1.00</CtrlSum>'
        . "<InitgPty><Nm>{$padding}</Nm></InitgPty></GrpHdr>"
        . '<PmtInf><PmtMtd>TRF</PmtMtd>'
        . '<Dbtr><Nm>D</Nm></Dbtr>'
        . '<DbtrAcct><Id><IBAN>GB29NWBK60161331926819</IBAN></Id></DbtrAcct>'
        . '<DbtrAgt><FinInstnId><BICFI>NWBKGB2L</BICFI></FinInstnId></DbtrAgt>'
        . '</PmtInf>'
        . '</CstmrCdtTrfInitn></Document>';

    $result = makeValidator()->validate($xml);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('exceeds maximum');
});
