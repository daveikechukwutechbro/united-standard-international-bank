<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pacs002
{
    /**
     * @param array<int, array{
     *   original_end_to_end_id: string,
     *   status: string,
     *   reason_code?: string,
     *   reason_info?: string,
     * }> $transactionStatuses
     */
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public string $originalMessageId,
        public string $originalMessageType,
        public string $groupStatus,
        public array $transactionStatuses,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: $data['message_id'],
            creationDateTime: new DateTimeImmutable($data['creation_date_time']),
            originalMessageId: $data['original_message_id'],
            originalMessageType: $data['original_message_type'],
            groupStatus: $data['group_status'],
            transactionStatuses: $data['transaction_statuses'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message_id'            => $this->messageId,
            'creation_date_time'    => $this->creationDateTime->format('Y-m-d\TH:i:s.vP'),
            'original_message_id'   => $this->originalMessageId,
            'original_message_type' => $this->originalMessageType,
            'group_status'          => $this->groupStatus,
            'transaction_statuses'  => $this->transactionStatuses,
        ];
    }

    public function toXml(): string
    {
        $txStatusXml = '';
        foreach ($this->transactionStatuses as $ts) {
            $reason = '';
            if (isset($ts['reason_code']) || isset($ts['reason_info'])) {
                $reasonCode = isset($ts['reason_code'])
                    ? "<Cd>{$ts['reason_code']}</Cd>"
                    : '';
                $reasonInfo = isset($ts['reason_info'])
                    ? "<AddtlInf>{$ts['reason_info']}</AddtlInf>"
                    : '';
                $reason = "<StsRsnInf><Rsn>{$reasonCode}</Rsn>{$reasonInfo}</StsRsnInf>";
            }

            $txStatusXml .= '<TxInfAndSts>'
                . "<OrgnlEndToEndId>{$ts['original_end_to_end_id']}</OrgnlEndToEndId>"
                . "<TxSts>{$ts['status']}</TxSts>"
                . $reason
                . '</TxInfAndSts>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.002.001.10">'
            . '<FIToFIPmtStsRpt>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . '</GrpHdr>'
            . '<OrgnlGrpInfAndSts>'
            . "<OrgnlMsgId>{$this->originalMessageId}</OrgnlMsgId>"
            . "<OrgnlMsgNmId>{$this->originalMessageType}</OrgnlMsgNmId>"
            . "<GrpSts>{$this->groupStatus}</GrpSts>"
            . '</OrgnlGrpInfAndSts>'
            . $txStatusXml
            . '</FIToFIPmtStsRpt></Document>';
    }
}
