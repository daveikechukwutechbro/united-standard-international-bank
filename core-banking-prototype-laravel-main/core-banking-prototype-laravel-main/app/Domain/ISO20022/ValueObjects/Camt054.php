<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Camt054
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
        public string $notificationId,
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
            notificationId: $data['notification_id'],
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
            'notification_id'    => $this->notificationId,
            'entries'            => $this->entries,
        ];
    }

    public function toXml(): string
    {
        $entriesXml = '';
        foreach ($this->entries as $entry) {
            $entriesXml .= '<Ntry>'
                . "<Amt>{$entry['amount']}</Amt>"
                . "<CdtDbtInd>{$entry['credit_debit']}</CdtDbtInd>"
                . "<BookgDt><Dt>{$entry['booking_date']}</Dt></BookgDt>"
                . "<NtryRef>{$entry['reference']}</NtryRef>"
                . "<NtryDtls><TxDtls><RmtInf><Ustrd>{$entry['description']}</Ustrd></RmtInf></TxDtls></NtryDtls>"
                . '</Ntry>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.054.001.08">'
            . '<BkToCstmrDbtCdtNtfctn>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm></GrpHdr>"
            . '<Ntfctn>'
            . "<Id>{$this->notificationId}</Id>"
            . "<Acct><Id><IBAN>{$this->accountIban}</IBAN></Id></Acct>"
            . $entriesXml
            . '</Ntfctn>'
            . '</BkToCstmrDbtCdtNtfctn></Document>';
    }
}
