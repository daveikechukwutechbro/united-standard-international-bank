<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pacs003
{
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public int $numberOfTransactions,
        public string $settlementMethod,
        public string $creditorAgentBic,
        public string $debtorAgentBic,
        public string $mandateId,
        public string $amount,
        public string $currency,
        public string $creditorName,
        public string $creditorIban,
        public string $debtorName,
        public string $debtorIban,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'],
            creationDateTime: new DateTimeImmutable($data['creation_date_time']),
            numberOfTransactions: (int) $data['number_of_transactions'],
            settlementMethod: $data['settlement_method'],
            creditorAgentBic: $data['creditor_agent_bic'],
            debtorAgentBic: $data['debtor_agent_bic'],
            mandateId: $data['mandate_id'],
            amount: $data['amount'],
            currency: $data['currency'],
            creditorName: $data['creditor_name'],
            creditorIban: $data['creditor_iban'],
            debtorName: $data['debtor_name'],
            debtorIban: $data['debtor_iban'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message_id'             => $this->messageId,
            'creation_date_time'     => $this->creationDateTime->format('Y-m-d\TH:i:s.vP'),
            'number_of_transactions' => $this->numberOfTransactions,
            'settlement_method'      => $this->settlementMethod,
            'creditor_agent_bic'     => $this->creditorAgentBic,
            'debtor_agent_bic'       => $this->debtorAgentBic,
            'mandate_id'             => $this->mandateId,
            'amount'                 => $this->amount,
            'currency'               => $this->currency,
            'creditor_name'          => $this->creditorName,
            'creditor_iban'          => $this->creditorIban,
            'debtor_name'            => $this->debtorName,
            'debtor_iban'            => $this->debtorIban,
        ];
    }

    public function toXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.003.001.08">'
            . '<FIToFICstmrDrctDbt>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . "<NbOfTxs>{$this->numberOfTransactions}</NbOfTxs>"
            . "<SttlmInf><SttlmMtd>{$this->settlementMethod}</SttlmMtd></SttlmInf>"
            . "<CdtrAgt><FinInstnId><BICFI>{$this->creditorAgentBic}</BICFI></FinInstnId></CdtrAgt>"
            . "<DbtrAgt><FinInstnId><BICFI>{$this->debtorAgentBic}</BICFI></FinInstnId></DbtrAgt>"
            . '</GrpHdr>'
            . '<DrctDbtTxInf>'
            . "<PmtId><EndToEndId>{$this->mandateId}</EndToEndId></PmtId>"
            . "<IntrBkSttlmAmt Ccy=\"{$this->currency}\">{$this->amount}</IntrBkSttlmAmt>"
            . "<DrctDbtTx><MndtRltdInf><MndtId>{$this->mandateId}</MndtId></MndtRltdInf></DrctDbtTx>"
            . "<Cdtr><Nm>{$this->creditorName}</Nm></Cdtr>"
            . "<CdtrAcct><Id><IBAN>{$this->creditorIban}</IBAN></Id></CdtrAcct>"
            . "<Dbtr><Nm>{$this->debtorName}</Nm></Dbtr>"
            . "<DbtrAcct><Id><IBAN>{$this->debtorIban}</IBAN></Id></DbtrAcct>"
            . '</DrctDbtTxInf>'
            . '</FIToFICstmrDrctDbt></Document>';
    }
}
