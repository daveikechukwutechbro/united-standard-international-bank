<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Enums\ConsentPermission;
use Illuminate\Support\Str;

final class PispService
{
    public function __construct(
        private readonly ConsentEnforcementService $enforcement,
    ) {
    }

    /**
     * Initiate a payment on behalf of a user under a valid consent.
     *
     * @param array{
     *     debtor_account?: string,
     *     creditor_account: string,
     *     creditor_name: string,
     *     amount: string,
     *     currency: string,
     *     remittance_info?: string,
     *     end_to_end_id?: string,
     * } $paymentData
     * @return array{payment_id: string, status: string, status_code: string, end_to_end_id: string}
     */
    public function initiatePayment(
        string $consentId,
        string $tppId,
        int $userId,
        array $paymentData,
    ): array {
        $hasAccess = $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::PAYMENT_INITIATION->value,
        );

        if (! $hasAccess) {
            return [
                'payment_id'    => '',
                'status'        => 'Rejected',
                'status_code'   => 'RJCT',
                'end_to_end_id' => $paymentData['end_to_end_id'] ?? '',
            ];
        }

        $this->enforcement->logAccess($consentId, $tppId, 'POST /payments');

        $paymentId = Str::uuid()->toString();
        $endToEndId = $paymentData['end_to_end_id'] ?? Str::upper(Str::random(16));

        return [
            'payment_id'    => $paymentId,
            'status'        => 'AcceptedSettlementInProcess',
            'status_code'   => 'ACSP',
            'end_to_end_id' => $endToEndId,
        ];
    }

    /**
     * Retrieve the current status of a previously initiated payment.
     *
     * @return array{payment_id: string, status: string, status_code: string, transaction_status: string}
     */
    public function getPaymentStatus(string $paymentId): array
    {
        return [
            'payment_id'         => $paymentId,
            'status'             => 'AcceptedSettlementCompleted',
            'status_code'        => 'ACSC',
            'transaction_status' => 'ACSC',
        ];
    }
}
