<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Services;

use App\Domain\PaymentRails\Enums\PaymentRail;
use App\Domain\PaymentRails\Enums\RailStatus;
use App\Domain\PaymentRails\Models\PaymentRailTransaction;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RtpService
{
    /**
     * Send a Real-Time Payment via The Clearing House RTP network.
     *
     * @throws InvalidArgumentException when amount exceeds the configured maximum
     * @return array{transaction_id: string, external_id: string, status: string, rail: string, amount: string, currency: string}
     */
    public function sendPayment(
        int $userId,
        string $amount,
        string $currency,
        string $creditorName,
        string $creditorAccountNumber,
        string $creditorRoutingNumber,
        ?string $reference = null,
    ): array {
        $maxAmount = (int) config('payment_rails.rtp.max_amount', 100000000);

        if ((int) round((float) $amount * 100) > $maxAmount) {
            throw new InvalidArgumentException(
                "RTP amount {$amount} exceeds maximum allowed {$maxAmount} cents."
            );
        }

        $externalId = 'RTP-' . strtoupper(Str::random(16));

        $transaction = PaymentRailTransaction::create([
            'user_id'     => $userId,
            'rail'        => PaymentRail::RTP,
            'external_id' => $externalId,
            'amount'      => $amount,
            'currency'    => $currency,
            'status'      => RailStatus::PROCESSING,
            'direction'   => 'debit',
            'metadata'    => [
                'creditor_name'           => $creditorName,
                'creditor_account_number' => $creditorAccountNumber,
                'creditor_routing_number' => $creditorRoutingNumber,
                'reference'               => $reference,
                'participant_id'          => config('payment_rails.rtp.participant_id'),
                'type'                    => 'send',
            ],
        ]);

        return [
            'transaction_id' => $transaction->id,
            'external_id'    => $externalId,
            'status'         => RailStatus::PROCESSING->value,
            'rail'           => PaymentRail::RTP->value,
            'amount'         => $amount,
            'currency'       => $currency,
        ];
    }

    /**
     * Issue a Request for Payment (RfP) via the RTP network.
     *
     * @return array{transaction_id: string, external_id: string, status: string, rail: string, amount: string, currency: string}
     */
    public function requestPayment(
        int $userId,
        string $amount,
        string $currency,
        string $debtorName,
        string $debtorRoutingNumber,
        ?string $reference = null,
    ): array {
        $externalId = 'RTP-RFP-' . strtoupper(Str::random(12));

        $transaction = PaymentRailTransaction::create([
            'user_id'     => $userId,
            'rail'        => PaymentRail::RTP,
            'external_id' => $externalId,
            'amount'      => $amount,
            'currency'    => $currency,
            'status'      => RailStatus::PENDING,
            'direction'   => 'credit',
            'metadata'    => [
                'debtor_name'           => $debtorName,
                'debtor_routing_number' => $debtorRoutingNumber,
                'reference'             => $reference,
                'participant_id'        => config('payment_rails.rtp.participant_id'),
                'type'                  => 'request',
            ],
        ]);

        return [
            'transaction_id' => $transaction->id,
            'external_id'    => $externalId,
            'status'         => RailStatus::PENDING->value,
            'rail'           => PaymentRail::RTP->value,
            'amount'         => $amount,
            'currency'       => $currency,
        ];
    }

    /**
     * Retrieve the status of an RTP transaction by internal transaction ID.
     *
     * @return array{transaction_id: string, external_id: string|null, status: string, rail: string, amount: string, currency: string}|null
     */
    public function getPaymentStatus(string $transactionId): ?array
    {
        $transaction = PaymentRailTransaction::where('id', $transactionId)
            ->where('rail', PaymentRail::RTP->value)
            ->first();

        if ($transaction === null) {
            return null;
        }

        return [
            'transaction_id' => $transaction->id,
            'external_id'    => $transaction->external_id,
            'status'         => $transaction->status->value,
            'rail'           => $transaction->rail->value,
            'amount'         => $transaction->amount,
            'currency'       => $transaction->currency,
        ];
    }
}
