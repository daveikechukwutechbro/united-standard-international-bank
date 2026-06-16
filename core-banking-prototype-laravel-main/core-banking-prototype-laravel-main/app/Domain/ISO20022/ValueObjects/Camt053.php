<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Camt053
{
    /**
     * @param array<int, array{
     *   amount: string,
     *   credit_debit: string,
     *   booking_date: string,
     *   reference: string,
     *   description: string,
     * }> $entries
     */
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public string $accountIban,
        public DateTimeImmutable $fromDate,
        public DateTimeImmutable $toDate,
        public string $openingBalance,
        public string $closingBalance,
        public string $currency,
        public array $entries,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'],
            creationDateTime: new DateTimeImmutable($data['creation_date_time']),
            accountIban: $data['account_iban'],
            fromDate: new DateTimeImmutable($data['from_date']),
            toDate: new DateTimeImmutable($data['to_date']),
            openingBalance: $data['opening_balance'],
            closingBalance: $data['closing_balance'],
            currency: $data['currency'],
            entries: $data['entries'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message_id'         => $this->messageId,
            'creation_date_time' => $this->creationDateTime->format('Y-m-d\TH:i:s.vP'),
            'account_iban'       => $this->accountIban,
            'from_date'          => $this->fromDate->format('Y-m-d'),
            'to_date'            => $this->toDate->format('Y-m-d'),
            'opening_balance'    => $this->openingBalance,
            'closing_balance'    => $this->closingBalance,
            'currency'           => $this->currency,
            'entries'            => $this->entries,
        ];
    }

    public function toXml(): string
    {
        $entriesXml = '';
        foreach ($this->entries as $entry) {
            $entriesXml .= '<Ntry>'
                . "<Amt Ccy=\"{$this->currency}\">{$entry['amount']}</Amt>"
                . "<CdtDbtInd>{$entry['credit_debit']}</CdtDbtInd>"
                . "<BookgDt><Dt>{$entry['booking_date']}</Dt></BookgDt>"
                . "<NtryRef>{$entry['reference']}</NtryRef>"
                . "<NtryDtls><TxDtls><RmtInf><Ustrd>{$entry['description']}</Ustrd></RmtInf></TxDtls></NtryDtls>"
                . '</Ntry>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.08">'
            . '<BkToCstmrStmt>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm></GrpHdr>"
            . '<Stmt>'
            . "<Acct><Id><IBAN>{$this->accountIban}</IBAN></Id></Acct>"
            . '<Bal><Tp><CdOrPrtry><Cd>OPBD</Cd></CdOrPrtry></Tp>'
            . "<Amt Ccy=\"{$this->currency}\">{$this->openingBalance}</Amt>"
            . '<CdtDbtInd>CRDT</CdtDbtInd></Bal>'
            . '<Bal><Tp><CdOrPrtry><Cd>CLBD</Cd></CdOrPrtry></Tp>'
            . "<Amt Ccy=\"{$this->currency}\">{$this->closingBalance}</Amt>"
            . '<CdtDbtInd>CRDT</CdtDbtInd></Bal>'
            . "<FrToDt><FrDtTm>{$this->fromDate->format('Y-m-d')}</FrDtTm>"
            . "<ToDtTm>{$this->toDate->format('Y-m-d')}</ToDtTm></FrToDt>"
            . $entriesXml
            . '</Stmt>'
            . '</BkToCstmrStmt></Document>';
    }
}
