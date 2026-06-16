<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Services;

use RuntimeException;
use SimpleXMLElement;

final class MessageParser
{
    public function __construct(
        private readonly MessageRegistry $registry,
    ) {
    }

    public function parseXml(string $xml): object
    {
        $messageType = $this->registry->detectMessageType($xml);

        if ($messageType === null) {
            throw new RuntimeException('Unrecognized ISO 20022 namespace in XML');
        }

        $data = match ($messageType) {
            'pain.001' => $this->parsePain001($xml),
            'pacs.008' => $this->parsePacs008($xml),
            'pacs.002' => $this->parsePacs002($xml),
            default    => $this->parseGeneric($xml),
        };

        return $this->parseArray($messageType, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function parseArray(string $messageType, array $data): object
    {
        $dtoClass = $this->registry->getDtoClass($messageType);

        if ($dtoClass === null) {
            throw new RuntimeException("Unsupported message type: {$messageType}");
        }

        /** @var object $dto */
        $dto = $dtoClass::fromArray($data);

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePain001(string $xml): array
    {
        $doc = new SimpleXMLElement($xml, LIBXML_NONET);
        $ns = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09';

        $root = $doc->children($ns);
        $msg = $root->CstmrCdtTrfInitn;
        $grpHdr = $msg->GrpHdr;
        $pmtInf = $msg->PmtInf;

        $transactions = [];
        foreach ($pmtInf->CdtTrfTxInf as $tx) {
            $transaction = [
                'end_to_end_id' => (string) $tx->PmtId->EndToEndId,
                'amount'        => (string) $tx->Amt->InstdAmt,
                'currency'      => (string) $tx->Amt->InstdAmt->attributes()['Ccy'],
                'creditor_name' => (string) $tx->Cdtr->Nm,
                'creditor_iban' => (string) $tx->CdtrAcct->Id->IBAN,
            ];

            if (isset($tx->CdtrAgt)) {
                $transaction['creditor_bic'] = (string) $tx->CdtrAgt->FinInstnId->BICFI;
            }

            if (isset($tx->RmtInf)) {
                $transaction['remittance_info'] = (string) $tx->RmtInf->Ustrd;
            }

            $transactions[] = $transaction;
        }

        return [
            'message_id'             => (string) $grpHdr->MsgId,
            'creation_date_time'     => (string) $grpHdr->CreDtTm,
            'number_of_transactions' => (int) (string) $grpHdr->NbOfTxs,
            'control_sum'            => (string) $grpHdr->CtrlSum,
            'initiating_party_name'  => (string) $grpHdr->InitgPty->Nm,
            'debtor_name'            => (string) $pmtInf->Dbtr->Nm,
            'debtor_iban'            => (string) $pmtInf->DbtrAcct->Id->IBAN,
            'debtor_bic'             => (string) $pmtInf->DbtrAgt->FinInstnId->BICFI,
            'payment_method'         => (string) $pmtInf->PmtMtd,
            'transactions'           => $transactions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePacs008(string $xml): array
    {
        $doc = new SimpleXMLElement($xml, LIBXML_NONET);
        $ns = 'urn:iso:std:iso:20022:tech:xsd:pacs.008.001.08';

        $root = $doc->children($ns);
        $msg = $root->FIToFICstmrCdtTrf;
        $grpHdr = $msg->GrpHdr;
        $tx = $msg->CdtTrfTxInf;

        return [
            'message_id'             => (string) $grpHdr->MsgId,
            'creation_date_time'     => (string) $grpHdr->CreDtTm,
            'number_of_transactions' => (int) (string) $grpHdr->NbOfTxs,
            'settlement_method'      => (string) $grpHdr->SttlmInf->SttlmMtd,
            'instructing_agent_bic'  => (string) $grpHdr->InstgAgt->FinInstnId->BICFI,
            'instructed_agent_bic'   => (string) $grpHdr->InstdAgt->FinInstnId->BICFI,
            'end_to_end_id'          => (string) $tx->PmtId->EndToEndId,
            'uetr'                   => (string) $tx->PmtId->UETR,
            'amount'                 => (string) $tx->IntrBkSttlmAmt,
            'currency'               => (string) $tx->IntrBkSttlmAmt->attributes()['Ccy'],
            'charge_bearer'          => (string) $tx->ChrgBr,
            'debtor_name'            => (string) $tx->Dbtr->Nm,
            'debtor_iban'            => (string) $tx->DbtrAcct->Id->IBAN,
            'creditor_name'          => (string) $tx->Cdtr->Nm,
            'creditor_iban'          => (string) $tx->CdtrAcct->Id->IBAN,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePacs002(string $xml): array
    {
        $doc = new SimpleXMLElement($xml, LIBXML_NONET);
        $ns = 'urn:iso:std:iso:20022:tech:xsd:pacs.002.001.10';

        $root = $doc->children($ns);
        $msg = $root->FIToFIPmtStsRpt;
        $grpHdr = $msg->GrpHdr;
        $orgnlGroup = $msg->OrgnlGrpInfAndSts;

        $transactionStatuses = [];
        foreach ($msg->TxInfAndSts as $ts) {
            $status = [
                'original_end_to_end_id' => (string) $ts->OrgnlEndToEndId,
                'status'                 => (string) $ts->TxSts,
            ];

            if (isset($ts->StsRsnInf)) {
                if (isset($ts->StsRsnInf->Rsn->Cd)) {
                    $status['reason_code'] = (string) $ts->StsRsnInf->Rsn->Cd;
                }

                if (isset($ts->StsRsnInf->AddtlInf)) {
                    $status['reason_info'] = (string) $ts->StsRsnInf->AddtlInf;
                }
            }

            $transactionStatuses[] = $status;
        }

        return [
            'message_id'            => (string) $grpHdr->MsgId,
            'creation_date_time'    => (string) $grpHdr->CreDtTm,
            'original_message_id'   => (string) $orgnlGroup->OrgnlMsgId,
            'original_message_type' => (string) $orgnlGroup->OrgnlMsgNmId,
            'group_status'          => (string) $orgnlGroup->GrpSts,
            'transaction_statuses'  => $transactionStatuses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseGeneric(string $xml): array
    {
        $doc = new SimpleXMLElement($xml, LIBXML_NONET);
        $json = json_encode($doc);

        if ($json === false) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true) ?? [];

        return $decoded;
    }
}
