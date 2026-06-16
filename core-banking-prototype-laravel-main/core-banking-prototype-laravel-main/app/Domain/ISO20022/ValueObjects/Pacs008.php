<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pacs008
{
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public int $numberOfTransactions,
        public string $settlementMethod,
        public string $instructingAgentBic,
        public string $instructedAgentBic,
        public string $endToEndId,
        public string $uetr,
        public string $amount,
        public string $currency,
        public string $debtorName,
        public string $debtorIban,
        public string $creditorName,
        public string $creditorIban,
        public string $chargeBearer = 'SLEV',
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
            instructingAgentBic: $data['instructing_agent_bic'],
            instructedAgentBic: $data['instructed_agent_bic'],
            endToEndId: $data['end_to_end_id'],
            uetr: $data['uetr'],
            amount: $data['amount'],
            currency: $data['currency'],
            debtorName: $data['debtor_name'],
            debtorIban: $data['debtor_iban'],
            creditorName: $data['creditor_name'],
            creditorIban: $data['creditor_iban'],
            chargeBearer: $data['charge_bearer'] ?? 'SLEV',
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
            'instructing_agent_bic'  => $this->instructingAgentBic,
            'instructed_agent_bic'   => $this->instructedAgentBic,
            'end_to_end_id'          => $this->endToEndId,
            'uetr'                   => $this->uetr,
            'amount'                 => $this->amount,
            'currency'               => $this->currency,
            'debtor_name'            => $this->debtorName,
            'debtor_iban'            => $this->debtorIban,
            'creditor_name'          => $this->creditorName,
            'creditor_iban'          => $this->creditorIban,
            'charge_bearer'          => $this->chargeBearer,
        ];
    }

    public function toXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.008.001.08">'
            . '<FIToFICstmrCdtTrf>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . "<NbOfTxs>{$this->numberOfTransactions}</NbOfTxs>"
            . "<SttlmInf><SttlmMtd>{$this->settlementMethod}</SttlmMtd></SttlmInf>"
            . "<InstgAgt><FinInstnId><BICFI>{$this->instructingAgentBic}</BICFI></FinInstnId></InstgAgt>"
            . "<InstdAgt><FinInstnId><BICFI>{$this->instructedAgentBic}</BICFI></FinInstnId></InstdAgt>"
            . '</GrpHdr>'
            . '<CdtTrfTxInf>'
            . "<PmtId><EndToEndId>{$this->endToEndId}</EndToEndId><UETR>{$this->uetr}</UETR></PmtId>"
            . "<IntrBkSttlmAmt Ccy=\"{$this->currency}\">{$this->amount}</IntrBkSttlmAmt>"
            . "<ChrgBr>{$this->chargeBearer}</ChrgBr>"
            . "<Dbtr><Nm>{$this->debtorName}</Nm></Dbtr>"
            . "<DbtrAcct><Id><IBAN>{$this->debtorIban}</IBAN></Id></DbtrAcct>"
            . "<Cdtr><Nm>{$this->creditorName}</Nm></Cdtr>"
            . "<CdtrAcct><Id><IBAN>{$this->creditorIban}</IBAN></Id></CdtrAcct>"
            . '</CdtTrfTxInf>'
            . '</FIToFICstmrCdtTrf></Document>';
    }
}
