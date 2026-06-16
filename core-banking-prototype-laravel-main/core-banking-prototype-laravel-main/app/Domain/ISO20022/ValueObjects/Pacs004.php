<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use DateTimeImmutable;

final readonly class Pacs004
{
    public function __construct(
        public string $messageId,
        public DateTimeImmutable $creationDateTime,
        public string $originalMessageId,
        public string $originalMessageType,
        public string $originalEndToEndId,
        public string $returnedAmount,
        public string $returnedCurrency,
        public string $returnReasonCode,
        public string $returnReasonInfo,
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
            originalEndToEndId: $data['original_end_to_end_id'],
            returnedAmount: $data['returned_amount'],
            returnedCurrency: $data['returned_currency'],
            returnReasonCode: $data['return_reason_code'],
            returnReasonInfo: $data['return_reason_info'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message_id'             => $this->messageId,
            'creation_date_time'     => $this->creationDateTime->format('Y-m-d\TH:i:s.vP'),
            'original_message_id'    => $this->originalMessageId,
            'original_message_type'  => $this->originalMessageType,
            'original_end_to_end_id' => $this->originalEndToEndId,
            'returned_amount'        => $this->returnedAmount,
            'returned_currency'      => $this->returnedCurrency,
            'return_reason_code'     => $this->returnReasonCode,
            'return_reason_info'     => $this->returnReasonInfo,
        ];
    }

    public function toXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pacs.004.001.09">'
            . '<PmtRtr>'
            . "<GrpHdr><MsgId>{$this->messageId}</MsgId>"
            . "<CreDtTm>{$this->creationDateTime->format('Y-m-d\TH:i:s')}</CreDtTm>"
            . '<NbOfTxs>1</NbOfTxs></GrpHdr>'
            . '<TxInf>'
            . "<OrgnlGrpInf><OrgnlMsgId>{$this->originalMessageId}</OrgnlMsgId>"
            . "<OrgnlMsgNmId>{$this->originalMessageType}</OrgnlMsgNmId></OrgnlGrpInf>"
            . "<OrgnlEndToEndId>{$this->originalEndToEndId}</OrgnlEndToEndId>"
            . "<RtrdIntrBkSttlmAmt Ccy=\"{$this->returnedCurrency}\">{$this->returnedAmount}</RtrdIntrBkSttlmAmt>"
            . "<RtrRsnInf><Rsn><Cd>{$this->returnReasonCode}</Cd></Rsn>"
            . "<AddtlInf>{$this->returnReasonInfo}</AddtlInf></RtrRsnInf>"
            . '</TxInf>'
            . '</PmtRtr></Document>';
    }
}
