<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pain001
{
    /**
     * @param array<int, array{
     *   end_to_end_id: string,
     *   amount: string,
     *   currency: string,
     *   creditor_name: string,
     *   creditor_iban: string,
     *   creditor_bic?: string,
     *   remittance_info?: string,
     * }> $transactions
     */
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public int $numberOfTransactions,
        public string $controlSum,
        public string $initiatingPartyName,
        public string $debtorName,
        public string $debtorIban,
        public string $debtorBic,
        public string $paymentMethod,
        public array $transactions,
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
            initiatingPartyName: $data['initiating_party_name'],
            debtorName: $data['debtor_name'],
            debtorIban: $data['debtor_iban'],
            debtorBic: $data['debtor_bic'],
            paymentMethod: $data['payment_method'] ?? 'TRF',
            transactions: $data['transactions'],
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
            'initiating_party_name'  => $this->initiatingPartyName,
            'debtor_name'            => $this->debtorName,
            'debtor_iban'            => $this->debtorIban,
            'debtor_bic'             => $this->debtorBic,
            'payment_method'         => $this->paymentMethod,
            'transactions'           => $this->transactions,
        ];
    }

    public function toXml(): string
    {
        $txXml = '';
        foreach ($this->transactions as $tx) {
            $remittance = isset($tx['remittance_info'])
                ? "<RmtInf><Ustrd>{$tx['remittance_info']}</Ustrd></RmtInf>"
                : '';
            $creditorBic = isset($tx['creditor_bic'])
                ? "<CdtrAgt><FinInstnId><BICFI>{$tx['creditor_bic']}</BICFI></FinInstnId></CdtrAgt>"
                : '';
            $txXml .= "<CdtTrfTxInf><PmtId><EndToEndId>{$tx['end_to_end_id']}</EndToEndId></PmtId>"
                . "<Amt><InstdAmt Ccy=\"{$tx['currency']}\">{$tx['amount']}</InstdAmt></Amt>"
                . $creditorBic
                . "<Cdtr><Nm>{$tx['creditor_name']}</Nm></Cdtr>"
                . "<CdtrAcct><Id><IBAN>{$tx['creditor_iban']}</IBAN></Id></CdtrAcct>"
                . $remittance
                . '</CdtTrfTxInf>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.09">'
            . '<CstmrCdtTrfInitn>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . "<NbOfTxs>{$this->numberOfTransactions}</NbOfTxs>"
            . "<CtrlSum>{$this->controlSum}</CtrlSum>"
            . "<InitgPty><Nm>{$this->initiatingPartyName}</Nm></InitgPty></GrpHdr>"
            . "<PmtInf><PmtMtd>{$this->paymentMethod}</PmtMtd>"
            . "<Dbtr><Nm>{$this->debtorName}</Nm></Dbtr>"
            . "<DbtrAcct><Id><IBAN>{$this->debtorIban}</IBAN></Id></DbtrAcct>"
            . "<DbtrAgt><FinInstnId><BICFI>{$this->debtorBic}</BICFI></FinInstnId></DbtrAgt>"
            . $txXml
            . '</PmtInf></CstmrCdtTrfInitn></Document>';
    }
}
