<?php

declare(strict_types=1);

namespace App\Domain\Banking\Services;

use App\Domain\ISO20022\ValueObjects\Pain008;
use DateTimeImmutable;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles SEPA Direct Debit collections via mandate-authorised debiting.
 *
 * Supports both CORE and B2B schemes. Generates ISO 20022 Pain008
 * (Customer Direct Debit Initiation) messages for submission to custodians.
 */
class SepaDirectDebitService
{
    private const CUSTODIAN_CREDITOR_BIC = 'FINAEGISXXX';

    public function __construct(
        private readonly SepaMandateService $mandateService,
    ) {
    }

    /**
     * Create a direct debit collection against a mandate.
     *
     * @return array{
     *   collection_id: string,
     *   mandate_id: string,
     *   amount: string,
     *   currency: string,
     *   reference: string,
     *   status: string,
     *   pain008: array<string, mixed>,
     *   pain008_xml: string,
     *   created_at: string,
     * }
     */
    public function createCollection(
        string $mandateId,
        string $amount,
        string $currency,
        ?string $reference = null,
    ): array {
        $mandate = $this->mandateService->findByMandateId($mandateId);

        if ($mandate === null) {
            throw new RuntimeException("SEPA mandate [{$mandateId}] not found.");
        }

        if (! $mandate->isActive()) {
            throw new RuntimeException(
                "Cannot create collection: mandate [{$mandateId}] is not active (status: {$mandate->status})."
            );
        }

        if ($mandate->max_amount !== null && (float) $amount > (float) $mandate->max_amount) {
            throw new RuntimeException(
                "Collection amount [{$amount}] exceeds mandate max_amount [{$mandate->max_amount}]."
            );
        }

        $collectionId = 'SDD-' . strtoupper(Str::uuid()->toString());
        $ref = $reference ?? 'REF-' . strtoupper(Str::random(10));
        $now = new DateTimeImmutable();

        $pain008 = new Pain008(
            messageId: $collectionId,
            creationDateTime: $now,
            numberOfTransactions: 1,
            controlSum: $amount,
            creditorName: $mandate->creditor_name,
            creditorIban: $mandate->creditor_iban,
            creditorBic: self::CUSTODIAN_CREDITOR_BIC,
            creditorSchemeId: $mandate->creditor_id,
            mandateId: $mandate->mandate_id,
            debtorName: $mandate->debtor_name,
            debtorIban: $mandate->debtor_iban,
            amount: $amount,
            currency: $currency,
            collectionDate: $now->modify('+1 day'),
        );

        return [
            'collection_id' => $collectionId,
            'mandate_id'    => $mandateId,
            'amount'        => $amount,
            'currency'      => $currency,
            'reference'     => $ref,
            'status'        => 'pending',
            'pain008'       => $pain008->toArray(),
            'pain008_xml'   => $pain008->toXml(),
            'created_at'    => $now->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Submit a pending collection to the custodian/clearing.
     *
     * @return array{
     *   collection_id: string,
     *   status: string,
     *   submitted_at: string,
     *   custodian_reference: string,
     *   estimated_settlement: string,
     * }
     */
    public function submitCollection(string $collectionId): array
    {
        // Demo/placeholder: custodian submission layer — real implementation
        // would POST the Pain008 XML to the custodian API endpoint.
        $now = new DateTimeImmutable();

        return [
            'collection_id'        => $collectionId,
            'status'               => 'submitted',
            'submitted_at'         => $now->format('Y-m-d\TH:i:s\Z'),
            'custodian_reference'  => 'CUST-' . strtoupper(Str::random(12)),
            'estimated_settlement' => $now->modify('+1 day')->format('Y-m-d'),
        ];
    }

    /**
     * Process a Direct Debit return or refusal from the debtor bank.
     *
     * @return array{
     *   collection_id: string,
     *   status: string,
     *   return_code: string,
     *   return_reason: string,
     *   processed_at: string,
     * }
     */
    public function processReturn(
        string $collectionId,
        string $returnCode,
        string $returnReason,
    ): array {
        $now = new DateTimeImmutable();

        return [
            'collection_id' => $collectionId,
            'status'        => 'returned',
            'return_code'   => $returnCode,
            'return_reason' => $returnReason,
            'processed_at'  => $now->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Retrieve collection history for a mandate.
     *
     * @return array<int, array{
     *   collection_id: string,
     *   mandate_id: string,
     *   status: string,
     *   created_at: string,
     * }>
     */
    public function getCollectionsByMandate(string $mandateId): array
    {
        // Demo/placeholder: returns an empty collection history.
        // Real implementation would query a sepa_collections table.
        return [];
    }
}
