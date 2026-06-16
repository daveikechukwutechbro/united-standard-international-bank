<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Domain\ISO20022\ValueObjects\Camt053;
use App\Domain\ISO20022\ValueObjects\Pacs002;
use App\Domain\ISO20022\ValueObjects\Pacs008;
use App\Domain\ISO20022\ValueObjects\Pain001;
use App\Domain\ISO20022\ValueObjects\Pain008;

// ── Pain001 ────────────────────────────────────────────────────────────────

it('creates Pain001 from array and round-trips toArray', function (): void {
    $data = [
        'message_id'             => 'MSG-001',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 2,
        'control_sum'            => '1500.00',
        'initiating_party_name'  => 'Acme Corp',
        'debtor_name'            => 'John Doe',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'payment_method'         => 'TRF',
        'transactions'           => [
            [
                'end_to_end_id' => 'E2E-001',
                'amount'        => '1000.00',
                'currency'      => 'EUR',
                'creditor_name' => 'Jane Smith',
                'creditor_iban' => 'DE89370400440532013000',
                'creditor_bic'  => 'DEUTDEDB',
            ],
            [
                'end_to_end_id'   => 'E2E-002',
                'amount'          => '500.00',
                'currency'        => 'EUR',
                'creditor_name'   => 'Bob Jones',
                'creditor_iban'   => 'FR7630006000011234567890189',
                'remittance_info' => 'Invoice #123',
            ],
        ],
    ];

    $pain001 = Pain001::fromArray($data);

    expect($pain001->messageId)->toBe('MSG-001');
    expect($pain001->numberOfTransactions)->toBe(2);
    expect($pain001->controlSum)->toBe('1500.00');
    expect($pain001->initiatingPartyName)->toBe('Acme Corp');
    expect($pain001->debtorName)->toBe('John Doe');
    expect($pain001->debtorBic)->toBe('NWBKGB2L');
    expect($pain001->paymentMethod)->toBe('TRF');
    expect($pain001->transactions)->toHaveCount(2);

    $array = $pain001->toArray();
    expect($array['message_id'])->toBe('MSG-001');
    expect($array['number_of_transactions'])->toBe(2);
    expect($array['control_sum'])->toBe('1500.00');
    expect($array['debtor_iban'])->toBe('GB29NWBK60161331926819');
    expect($array['transactions'])->toHaveCount(2);
});

it('defaults payment_method to TRF when not provided', function (): void {
    $data = [
        'message_id'             => 'MSG-002',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '100.00',
        'initiating_party_name'  => 'Corp',
        'debtor_name'            => 'Debtor',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'transactions'           => [],
    ];

    $pain001 = Pain001::fromArray($data);

    expect($pain001->paymentMethod)->toBe('TRF');
});

it('generates valid Pain001 XML with correct namespace', function (): void {
    $pain001 = Pain001::fromArray([
        'message_id'             => 'MSG-XML-001',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '250.00',
        'initiating_party_name'  => 'Test Corp',
        'debtor_name'            => 'Alice',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'payment_method'         => 'TRF',
        'transactions'           => [
            [
                'end_to_end_id' => 'E2E-XML-001',
                'amount'        => '250.00',
                'currency'      => 'EUR',
                'creditor_name' => 'Bob',
                'creditor_iban' => 'DE89370400440532013000',
            ],
        ],
    ]);

    $xml = $pain001->toXml();

    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($xml)->toContain('xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.09"');
    expect($xml)->toContain('<CstmrCdtTrfInitn>');
    expect($xml)->toContain('<MsgId>MSG-XML-001</MsgId>');
    expect($xml)->toContain('<NbOfTxs>1</NbOfTxs>');
    expect($xml)->toContain('<CtrlSum>250.00</CtrlSum>');
    expect($xml)->toContain('<Nm>Alice</Nm>');
    expect($xml)->toContain('<IBAN>GB29NWBK60161331926819</IBAN>');
    expect($xml)->toContain('<EndToEndId>E2E-XML-001</EndToEndId>');
    expect($xml)->toContain('Ccy="EUR"');
    expect($xml)->toContain('<IBAN>DE89370400440532013000</IBAN>');
});

it('includes creditor BIC in Pain001 XML when provided', function (): void {
    $pain001 = Pain001::fromArray([
        'message_id'             => 'MSG-BIC',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '100.00',
        'initiating_party_name'  => 'Corp',
        'debtor_name'            => 'Debtor',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'transactions'           => [
            [
                'end_to_end_id' => 'E2E',
                'amount'        => '100.00',
                'currency'      => 'EUR',
                'creditor_name' => 'Creditor',
                'creditor_iban' => 'DE89370400440532013000',
                'creditor_bic'  => 'DEUTDEDB',
            ],
        ],
    ]);

    $xml = $pain001->toXml();
    expect($xml)->toContain('<BICFI>DEUTDEDB</BICFI>');
});

it('includes remittance info in Pain001 XML when provided', function (): void {
    $pain001 = Pain001::fromArray([
        'message_id'             => 'MSG-RMT',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '100.00',
        'initiating_party_name'  => 'Corp',
        'debtor_name'            => 'Debtor',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'debtor_bic'             => 'NWBKGB2L',
        'transactions'           => [
            [
                'end_to_end_id'   => 'E2E',
                'amount'          => '100.00',
                'currency'        => 'EUR',
                'creditor_name'   => 'Creditor',
                'creditor_iban'   => 'DE89370400440532013000',
                'remittance_info' => 'Invoice #456',
            ],
        ],
    ]);

    $xml = $pain001->toXml();
    expect($xml)->toContain('<RmtInf><Ustrd>Invoice #456</Ustrd></RmtInf>');
});

// ── Pacs008 ────────────────────────────────────────────────────────────────

it('creates Pacs008 from array', function (): void {
    $data = [
        'message_id'             => 'PACS008-001',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'settlement_method'      => 'CLRG',
        'instructing_agent_bic'  => 'BANKUS33',
        'instructed_agent_bic'   => 'BANKGB2L',
        'end_to_end_id'          => 'E2E-PACS-001',
        'uetr'                   => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'amount'                 => '5000.00',
        'currency'               => 'USD',
        'debtor_name'            => 'US Corp',
        'debtor_iban'            => 'US12345678901234567890',
        'creditor_name'          => 'UK Ltd',
        'creditor_iban'          => 'GB29NWBK60161331926819',
    ];

    $pacs008 = Pacs008::fromArray($data);

    expect($pacs008->messageId)->toBe('PACS008-001');
    expect($pacs008->settlementMethod)->toBe('CLRG');
    expect($pacs008->instructingAgentBic)->toBe('BANKUS33');
    expect($pacs008->instructedAgentBic)->toBe('BANKGB2L');
    expect($pacs008->uetr)->toBe('f47ac10b-58cc-4372-a567-0e02b2c3d479');
    expect($pacs008->chargeBearer)->toBe('SLEV');
});

it('generates valid Pacs008 XML with correct namespace', function (): void {
    $pacs008 = Pacs008::fromArray([
        'message_id'             => 'PACS008-XML',
        'creation_date_time'     => '2024-01-15T10:00:00',
        'number_of_transactions' => 1,
        'settlement_method'      => 'CLRG',
        'instructing_agent_bic'  => 'BANKUS33',
        'instructed_agent_bic'   => 'BANKGB2L',
        'end_to_end_id'          => 'E2E-PACS-XML',
        'uetr'                   => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'amount'                 => '1000.00',
        'currency'               => 'EUR',
        'debtor_name'            => 'Sender',
        'debtor_iban'            => 'DE89370400440532013000',
        'creditor_name'          => 'Receiver',
        'creditor_iban'          => 'GB29NWBK60161331926819',
    ]);

    $xml = $pacs008->toXml();

    expect($xml)->toContain('xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.008.001.08"');
    expect($xml)->toContain('<FIToFICstmrCdtTrf>');
    expect($xml)->toContain('<MsgId>PACS008-XML</MsgId>');
    expect($xml)->toContain('<UETR>f47ac10b-58cc-4372-a567-0e02b2c3d479</UETR>');
    expect($xml)->toContain('<SttlmMtd>CLRG</SttlmMtd>');
    expect($xml)->toContain('<ChrgBr>SLEV</ChrgBr>');
    expect($xml)->toContain('<BICFI>BANKUS33</BICFI>');
    expect($xml)->toContain('<BICFI>BANKGB2L</BICFI>');
});

it('round-trips Pacs008 toArray', function (): void {
    $data = [
        'message_id'             => 'PACS008-RT',
        'creation_date_time'     => '2024-06-01T08:30:00',
        'number_of_transactions' => 1,
        'settlement_method'      => 'INDA',
        'instructing_agent_bic'  => 'AAAAAA11',
        'instructed_agent_bic'   => 'BBBBBB22',
        'end_to_end_id'          => 'RT-001',
        'uetr'                   => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        'amount'                 => '999.99',
        'currency'               => 'GBP',
        'debtor_name'            => 'D Name',
        'debtor_iban'            => 'GB29NWBK60161331926819',
        'creditor_name'          => 'C Name',
        'creditor_iban'          => 'DE89370400440532013000',
        'charge_bearer'          => 'DEBT',
    ];

    $pacs008 = Pacs008::fromArray($data);
    $array = $pacs008->toArray();

    expect($array['message_id'])->toBe('PACS008-RT');
    expect($array['settlement_method'])->toBe('INDA');
    expect($array['charge_bearer'])->toBe('DEBT');
    expect($array['amount'])->toBe('999.99');
});

// ── Pacs002 ────────────────────────────────────────────────────────────────

it('creates Pacs002 status report', function (): void {
    $pacs002 = Pacs002::fromArray([
        'message_id'            => 'PACS002-001',
        'creation_date_time'    => '2024-01-15T10:05:00',
        'original_message_id'   => 'PACS008-001',
        'original_message_type' => 'pacs.008.001.08',
        'group_status'          => 'ACCP',
        'transaction_statuses'  => [
            [
                'original_end_to_end_id' => 'E2E-001',
                'status'                 => 'ACSP',
            ],
            [
                'original_end_to_end_id' => 'E2E-002',
                'status'                 => 'RJCT',
                'reason_code'            => 'AC01',
                'reason_info'            => 'Incorrect account number',
            ],
        ],
    ]);

    expect($pacs002->messageId)->toBe('PACS002-001');
    expect($pacs002->originalMessageId)->toBe('PACS008-001');
    expect($pacs002->groupStatus)->toBe('ACCP');
    expect($pacs002->transactionStatuses)->toHaveCount(2);
});

it('generates valid Pacs002 XML with transaction statuses', function (): void {
    $pacs002 = Pacs002::fromArray([
        'message_id'            => 'PACS002-XML',
        'creation_date_time'    => '2024-01-15T10:05:00',
        'original_message_id'   => 'ORIG-001',
        'original_message_type' => 'pacs.008.001.08',
        'group_status'          => 'PART',
        'transaction_statuses'  => [
            [
                'original_end_to_end_id' => 'E2E-001',
                'status'                 => 'ACSC',
                'reason_code'            => 'NARR',
                'reason_info'            => 'Processed successfully',
            ],
        ],
    ]);

    $xml = $pacs002->toXml();

    expect($xml)->toContain('xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.002.001.10"');
    expect($xml)->toContain('<FIToFIPmtStsRpt>');
    expect($xml)->toContain('<MsgId>PACS002-XML</MsgId>');
    expect($xml)->toContain('<OrgnlMsgId>ORIG-001</OrgnlMsgId>');
    expect($xml)->toContain('<GrpSts>PART</GrpSts>');
    expect($xml)->toContain('<OrgnlEndToEndId>E2E-001</OrgnlEndToEndId>');
    expect($xml)->toContain('<TxSts>ACSC</TxSts>');
    expect($xml)->toContain('<Cd>NARR</Cd>');
    expect($xml)->toContain('<AddtlInf>Processed successfully</AddtlInf>');
});

// ── Camt053 ────────────────────────────────────────────────────────────────

it('creates Camt053 bank statement with entries', function (): void {
    $camt053 = Camt053::fromArray([
        'message_id'         => 'CAMT053-001',
        'creation_date_time' => '2024-01-31T23:59:59',
        'account_iban'       => 'GB29NWBK60161331926819',
        'from_date'          => '2024-01-01',
        'to_date'            => '2024-01-31',
        'opening_balance'    => '10000.00',
        'closing_balance'    => '12500.00',
        'currency'           => 'GBP',
        'entries'            => [
            [
                'amount'       => '3000.00',
                'credit_debit' => 'CRDT',
                'booking_date' => '2024-01-15',
                'reference'    => 'TRF-001',
                'description'  => 'Salary payment',
            ],
            [
                'amount'       => '500.00',
                'credit_debit' => 'DBIT',
                'booking_date' => '2024-01-20',
                'reference'    => 'DD-001',
                'description'  => 'Utility bill',
            ],
        ],
    ]);

    expect($camt053->messageId)->toBe('CAMT053-001');
    expect($camt053->accountIban)->toBe('GB29NWBK60161331926819');
    expect($camt053->openingBalance)->toBe('10000.00');
    expect($camt053->closingBalance)->toBe('12500.00');
    expect($camt053->currency)->toBe('GBP');
    expect($camt053->entries)->toHaveCount(2);
});

it('generates valid Camt053 XML with namespace and entries', function (): void {
    $camt053 = Camt053::fromArray([
        'message_id'         => 'CAMT053-XML',
        'creation_date_time' => '2024-01-31T23:59:59',
        'account_iban'       => 'DE89370400440532013000',
        'from_date'          => '2024-01-01',
        'to_date'            => '2024-01-31',
        'opening_balance'    => '5000.00',
        'closing_balance'    => '6200.00',
        'currency'           => 'EUR',
        'entries'            => [
            [
                'amount'       => '1200.00',
                'credit_debit' => 'CRDT',
                'booking_date' => '2024-01-10',
                'reference'    => 'REF-001',
                'description'  => 'Wire transfer',
            ],
        ],
    ]);

    $xml = $camt053->toXml();

    expect($xml)->toContain('xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.08"');
    expect($xml)->toContain('<BkToCstmrStmt>');
    expect($xml)->toContain('<MsgId>CAMT053-XML</MsgId>');
    expect($xml)->toContain('<IBAN>DE89370400440532013000</IBAN>');
    expect($xml)->toContain('<Cd>OPBD</Cd>');
    expect($xml)->toContain('<Cd>CLBD</Cd>');
    expect($xml)->toContain('<CdtDbtInd>CRDT</CdtDbtInd>');
    expect($xml)->toContain('<NtryRef>REF-001</NtryRef>');
    expect($xml)->toContain('<Ustrd>Wire transfer</Ustrd>');
});

it('round-trips Camt053 toArray', function (): void {
    $camt053 = Camt053::fromArray([
        'message_id'         => 'CAMT053-RT',
        'creation_date_time' => '2024-03-31T23:59:59',
        'account_iban'       => 'FR7630006000011234567890189',
        'from_date'          => '2024-03-01',
        'to_date'            => '2024-03-31',
        'opening_balance'    => '2000.00',
        'closing_balance'    => '2500.00',
        'currency'           => 'EUR',
        'entries'            => [],
    ]);

    $array = $camt053->toArray();

    expect($array['message_id'])->toBe('CAMT053-RT');
    expect($array['account_iban'])->toBe('FR7630006000011234567890189');
    expect($array['from_date'])->toBe('2024-03-01');
    expect($array['to_date'])->toBe('2024-03-31');
    expect($array['opening_balance'])->toBe('2000.00');
    expect($array['entries'])->toBe([]);
});

// ── Pain008 ────────────────────────────────────────────────────────────────

it('creates Pain008 direct debit from array', function (): void {
    $pain008 = Pain008::fromArray([
        'message_id'             => 'PAIN008-001',
        'creation_date_time'     => '2024-02-01T09:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '300.00',
        'creditor_name'          => 'Utility Co',
        'creditor_iban'          => 'GB29NWBK60161331926819',
        'creditor_bic'           => 'NWBKGB2L',
        'creditor_scheme_id'     => 'GB12ZZZ01234567',
        'mandate_id'             => 'MND-2024-001',
        'debtor_name'            => 'Jane Customer',
        'debtor_iban'            => 'DE89370400440532013000',
        'amount'                 => '300.00',
        'currency'               => 'GBP',
        'collection_date'        => '2024-02-15',
    ]);

    expect($pain008->messageId)->toBe('PAIN008-001');
    expect($pain008->creditorName)->toBe('Utility Co');
    expect($pain008->creditorSchemeId)->toBe('GB12ZZZ01234567');
    expect($pain008->mandateId)->toBe('MND-2024-001');
    expect($pain008->collectionDate->format('Y-m-d'))->toBe('2024-02-15');
});

it('generates valid Pain008 XML with direct debit namespace', function (): void {
    $pain008 = Pain008::fromArray([
        'message_id'             => 'PAIN008-XML',
        'creation_date_time'     => '2024-02-01T09:00:00',
        'number_of_transactions' => 1,
        'control_sum'            => '150.00',
        'creditor_name'          => 'Magazine Sub',
        'creditor_iban'          => 'GB29NWBK60161331926819',
        'creditor_bic'           => 'NWBKGB2L',
        'creditor_scheme_id'     => 'GB98ZZZ99999999',
        'mandate_id'             => 'MND-XML-001',
        'debtor_name'            => 'Subscriber',
        'debtor_iban'            => 'FR7630006000011234567890189',
        'amount'                 => '150.00',
        'currency'               => 'EUR',
        'collection_date'        => '2024-02-28',
    ]);

    $xml = $pain008->toXml();

    expect($xml)->toContain('xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.08"');
    expect($xml)->toContain('<CstmrDrctDbtInitn>');
    expect($xml)->toContain('<MsgId>PAIN008-XML</MsgId>');
    expect($xml)->toContain('<PmtMtd>DD</PmtMtd>');
    expect($xml)->toContain('<ReqdColltnDt>2024-02-28</ReqdColltnDt>');
    expect($xml)->toContain('<Nm>Magazine Sub</Nm>');
    expect($xml)->toContain('<IBAN>GB29NWBK60161331926819</IBAN>');
    expect($xml)->toContain('<Id>GB98ZZZ99999999</Id>');
    expect($xml)->toContain('<MndtId>MND-XML-001</MndtId>');
    expect($xml)->toContain('<Nm>Subscriber</Nm>');
    expect($xml)->toContain('<IBAN>FR7630006000011234567890189</IBAN>');
});
