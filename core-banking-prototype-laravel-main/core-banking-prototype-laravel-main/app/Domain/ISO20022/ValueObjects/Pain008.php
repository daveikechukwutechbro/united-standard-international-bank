<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pain008
{
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public int $numberOfTransactions,
        public string $controlSum,
        public string $creditorName,
        public string $creditorIban,
        public string $creditorBic,
        public string $creditorSchemeId,
        public string $mandateId,
        public string $debtorName,
        public string $debtorIban,
        public string $amount,
        public string $currency,
        public DateTimeImmutable $collectionDate,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'],
            creationDateTime: new DateTimeImmutable($data['creation_date_time']),
            numberOfTransactions: (int) $data['number_of_transactions'],
            controlSum: $data['control_sum'],
            creditorName: $data['creditor_name'],
            creditorIban: $data['creditor_iban'],
            creditorBic: $data['creditor_bic'],
            creditorSchemeId: $data['creditor_scheme_id'],
            mandateId: $data['mandate_id'],
            debtorName: $data['debtor_name'],
            debtorIban: $data['debtor_iban'],
            amount: $data['amount'],
            currency: $data['currency'],
            collectionDate: new DateTimeImmutable($data['collection_date']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message_id'             => $this->messageId,
            'creation_date_time'     => $this->creationDateTime->format('Y-m-d\TH:i:s.vP'),
            'number_of_transactions' => $this->numberOfTransactions,
            'control_sum'            => $this->controlSum,
            'creditor_name'          => $this->creditorName,
            'creditor_iban'          => $this->creditorIban,
            'creditor_bic'           => $this->creditorBic,
            'creditor_scheme_id'     => $this->creditorSchemeId,
            'mandate_id'             => $this->mandateId,
            'debtor_name'            => $this->debtorName,
            'debtor_iban'            => $this->debtorIban,
            'amount'                 => $this->amount,
            'currency'               => $this->currency,
            'collection_date'        => $this->collectionDate->format('Y-m-d'),
        ];
    }

    public function toXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.08">'
            . '<CstmrDrctDbtInitn>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . "<NbOfTxs>{$this->numberOfTransactions}</NbOfTxs>"
            . "<CtrlSum>{$this->controlSum}</CtrlSum></GrpHdr>"
            . '<PmtInf>'
            . '<PmtMtd>DD</PmtMtd>'
            . "<ReqdColltnDt>{$this->collectionDate->format('Y-m-d')}</ReqdColltnDt>"
            . "<Cdtr><Nm>{$this->creditorName}</Nm></Cdtr>"
            . "<CdtrAcct><Id><IBAN>{$this->creditorIban}</IBAN></Id></CdtrAcct>"
            . "<CdtrAgt><FinInstnId><BICFI>{$this->creditorBic}</BICFI></FinInstnId></CdtrAgt>"
            . "<CdtrSchmeId><Id><PrvtId><Othr><Id>{$this->creditorSchemeId}</Id>"
            . '<SchmeNm><Prtry>SEPA</Prtry></SchmeNm></Othr></PrvtId></Id></CdtrSchmeId>'
            . '<DrctDbtTxInf>'
            . "<PmtId><EndToEndId>{$this->mandateId}</EndToEndId></PmtId>"
            . "<InstdAmt Ccy=\"{$this->currency}\">{$this->amount}</InstdAmt>"
            . "<DrctDbtTx><MndtRltdInf><MndtId>{$this->mandateId}</MndtId></MndtRltdInf></DrctDbtTx>"
            . "<Dbtr><Nm>{$this->debtorName}</Nm></Dbtr>"
            . "<DbtrAcct><Id><IBAN>{$this->debtorIban}</IBAN></Id></DbtrAcct>"
            . '</DrctDbtTxInf>'
            . '</PmtInf>'
            . '</CstmrDrctDbtInitn></Document>';
    }
}
