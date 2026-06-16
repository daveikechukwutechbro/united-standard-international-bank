<?php

declare(strict_types=1);

namespace App\Domain\Banking\Services;

use App\Domain\ISO20022\ValueObjects\Pacs008;
use App\Domain\ISO20022\ValueObjects\Pain001;
use DateTimeImmutable;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SEPA Credit Transfer (SCT) and SEPA Instant Credit Transfer (SCT Inst).
 *
 * Standard SCT generates ISO 20022 Pain001 (Customer Credit Transfer Initiation).
 * Instant SCT generates ISO 20022 Pacs008 (FI-to-FI Customer Credit Transfer)
 * for near-real-time settlement within 10 seconds.
 */
class SepaCreditTransferService
{
    private const DEBTOR_BIC = 'FINAEGISXXX';

    private const CREDITOR_BIC = 'UNKNOWN000';

    /**
     * Initiate a SEPA Credit Transfer or Instant Credit Transfer.
     *
     * @return array{
     *   transfer_id: string,
     *   type: string,
     *   status: string,
     *   creditor_iban: string,
     *   creditor_name: string,
     *   amount: string,
     *   currency: string,
     *   reference: string,
     *   instant: bool,
     *   iso20022_message: array<string, mixed>,
     *   iso20022_xml: string,
     *   created_at: string,
     *   estimated_settlement: string,
     * }
     */
    public function initiateTransfer(
        int $userId,
        string $creditorIban,
        string $creditorName,
        string $amount,
        string $currency,
        bool $instant = false,
        ?string $reference = null,
    ): array {
        $transferId = ($instant ? 'SCTINST-' : 'SCT-') . strtoupper(Str::uuid()->toString());
        $endToEndId = $reference ?? 'E2E-' . strtoupper(Str::random(12));
        $now = new DateTimeImmutable();

        if ($instant) {
            $message = $this->buildPacs008($transferId, $now, $amount, $currency, $creditorName, $creditorIban, $endToEndId);
            $messageArr = $message->toArray();
            $messageXml = $message->toXml();
            $estimated = $now->modify('+10 seconds')->format('Y-m-d\TH:i:s\Z');
            $type = 'SEPA_INSTANT';
        } else {
            $message = $this->buildPain001($transferId, $now, $amount, $currency, $creditorName, $creditorIban, $endToEndId);
            $messageArr = $message->toArray();
            $messageXml = $message->toXml();
            $estimated = $now->modify('+1 day')->format('Y-m-d');
            $type = 'SEPA';
        }

        return [
            'transfer_id'          => $transferId,
            'type'                 => $type,
            'status'               => 'pending',
            'creditor_iban'        => $creditorIban,
            'creditor_name'        => $creditorName,
            'amount'               => $amount,
            'currency'             => $currency,
            'reference'            => $endToEndId,
            'instant'              => $instant,
            'iso20022_message'     => $messageArr,
            'iso20022_xml'         => $messageXml,
            'created_at'           => $now->format('Y-m-d\TH:i:s\Z'),
            'estimated_settlement' => $estimated,
        ];
    }

    /**
     * Get the current status of a credit transfer.
     *
     * @return array{
     *   transfer_id: string,
     *   status: string,
     *   checked_at: string,
     * }
     */
    public function getTransferStatus(string $transferId): array
    {
        // Demo/placeholder: real implementation queries custodian or internal DB.
        return [
            'transfer_id' => $transferId,
            'status'      => 'pending',
            'checked_at'  => (new DateTimeImmutable())->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Cancel a pending (non-instant) credit transfer.
     *
     * @return array{
     *   transfer_id: string,
     *   status: string,
     *   cancelled_at: string,
     * }
     *
     * @throws RuntimeException If the transfer cannot be cancelled.
     */
    public function cancelTransfer(string $transferId): array
    {
        if (str_starts_with($transferId, 'SCTINST-')) {
            throw new RuntimeException(
                "Instant credit transfer [{$transferId}] cannot be cancelled after submission."
            );
        }

        $now = new DateTimeImmutable();

        return [
            'transfer_id'  => $transferId,
            'status'       => 'cancelled',
            'cancelled_at' => $now->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Build a Pain001 message for standard SCT.
     */
    private function buildPain001(
        string $transferId,
        DateTimeImmutable $now,
        string $amount,
        string $currency,
        string $creditorName,
        string $creditorIban,
        string $endToEndId,
    ): Pain001 {
        return new Pain001(
            messageId: $transferId,
            creationDateTime: $now,
            numberOfTransactions: 1,
            controlSum: $amount,
            initiatingPartyName: 'FinAegis',
            debtorName: 'FinAegis Customer',
            debtorIban: 'DE00000000000000000000',
            debtorBic: self::DEBTOR_BIC,
            paymentMethod: 'TRF',
            transactions: [
                [
                    'end_to_end_id' => $endToEndId,
                    'amount'        => $amount,
                    'currency'      => $currency,
                    'creditor_name' => $creditorName,
                    'creditor_iban' => $creditorIban,
                ],
            ],
        );
    }

    /**
     * Build a Pacs008 message for SCT Inst.
     */
    private function buildPacs008(
        string $transferId,
        DateTimeImmutable $now,
        string $amount,
        string $currency,
        string $creditorName,
        string $creditorIban,
        string $endToEndId,
    ): Pacs008 {
        return new Pacs008(
            messageId: $transferId,
            creationDateTime: $now,
            numberOfTransactions: 1,
            settlementMethod: 'CLRG',
            instructingAgentBic: self::DEBTOR_BIC,
            instructedAgentBic: self::CREDITOR_BIC,
            endToEndId: $endToEndId,
            uetr: Str::uuid()->toString(),
            amount: $amount,
            currency: $currency,
            debtorName: 'FinAegis Customer',
            debtorIban: 'DE00000000000000000000',
            creditorName: $creditorName,
            creditorIban: $creditorIban,
        );
    }
}
