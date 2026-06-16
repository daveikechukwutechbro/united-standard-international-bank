<?php

declare(strict_types=1);

namespace App\Domain\Interledger\Services;

use App\Domain\Interledger\Enums\PaymentState;

/**
 * GNAP-based authorization and payment operations for the Open Payments standard.
 *
 * Open Payments (https://openpayments.guide) is an API standard built on ILP
 * that enables third-party initiation of payments via wallet addresses and
 * GNAP (Grant Negotiation and Authorization Protocol) access tokens.
 *
 * This service provides an in-process simulation suitable for integration
 * testing and demo environments.  Production deployments should replace this
 * with HTTP calls to a real Open Payments resource server (e.g. Rafiki).
 */
class OpenPaymentsService
{
    public function __construct(
        private readonly IlpAddressResolver $addressResolver = new IlpAddressResolver(),
    ) {
    }

    /**
     * Create an incoming payment resource at the given wallet address.
     *
     * @return array{incoming_payment_id: string, ilp_address: string, shared_secret: string, wallet_address: string, amount: string, asset_code: string, expires_at: string}
     */
    public function createIncomingPayment(
        string $walletAddress,
        string $amount,
        string $assetCode,
    ): array {
        $incomingPaymentId = (string) \Illuminate\Support\Str::uuid();
        $ilpAddress = $this->walletAddressToIlp($walletAddress) . '.incoming.' . $incomingPaymentId;
        $sharedSecret = bin2hex(random_bytes(32));
        $grantTtl = (int) config('interledger.open_payments.grant_ttl_seconds', 3600);

        return [
            'incoming_payment_id' => $incomingPaymentId,
            'ilp_address'         => $ilpAddress,
            'shared_secret'       => $sharedSecret,
            'wallet_address'      => $walletAddress,
            'amount'              => $amount,
            'asset_code'          => $assetCode,
            'expires_at'          => now()->addSeconds($grantTtl)->toIso8601String(),
        ];
    }

    /**
     * Create an outgoing payment resource, initiating a payment against a quote.
     *
     * @return array{outgoing_payment_id: string, status: string, wallet_address: string, quote_id: string, created_at: string}
     */
    public function createOutgoingPayment(
        string $walletAddress,
        string $quoteId,
        string $grantToken,
    ): array {
        // In production: POST to the Open Payments resource server using the
        // grant token for authorization (GNAP continuation access token).
        $outgoingPaymentId = (string) \Illuminate\Support\Str::uuid();

        return [
            'outgoing_payment_id' => $outgoingPaymentId,
            'status'              => PaymentState::SENDING->value,
            'wallet_address'      => $walletAddress,
            'quote_id'            => $quoteId,
            'created_at'          => now()->toIso8601String(),
        ];
    }

    /**
     * Create a quote resource, calculating the amounts for a proposed payment.
     *
     * @return array{quote_id: string, receive_amount: string, debit_amount: string, exchange_rate: float, expires_at: string, receiver: string, asset_code: string}
     */
    public function createQuote(
        string $walletAddress,
        string $receiverAddress,
        string $amount,
        string $assetCode,
    ): array {
        $quoteId = (string) \Illuminate\Support\Str::uuid();

        // Derive a simple debit amount including a 0.1 % connector fee.
        $amountFloat = (float) $amount;
        $debitAmount = number_format($amountFloat * 1.001, 2, '.', '');
        $exchangeRate = 1.0; // Same-currency by default; cross-currency handled by QuoteService.

        return [
            'quote_id'       => $quoteId,
            'receive_amount' => $amount,
            'debit_amount'   => $debitAmount,
            'exchange_rate'  => $exchangeRate,
            'expires_at'     => now()->addSeconds(30)->toIso8601String(),
            'receiver'       => $receiverAddress,
            'asset_code'     => $assetCode,
        ];
    }

    /**
     * Retrieve the current status of an outgoing or incoming payment.
     *
     * @return array{payment_id: string, status: string, checked_at: string}
     */
    public function getPaymentStatus(string $paymentId): array
    {
        // In production: GET from the Open Payments resource server.
        return [
            'payment_id' => $paymentId,
            'status'     => PaymentState::COMPLETED->value,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Convert a wallet address (payment pointer or URL) to an ILP address.
     */
    private function walletAddressToIlp(string $walletAddress): string
    {
        if (str_starts_with($walletAddress, '$')) {
            return $this->addressResolver->fromPaymentPointer($walletAddress);
        }

        // Treat as a URL — strip scheme and convert to dotted segments.
        $host = (string) (parse_url($walletAddress, PHP_URL_HOST) ?? $walletAddress);
        $path = ltrim((string) (parse_url($walletAddress, PHP_URL_PATH) ?? ''), '/');

        /** @var string[] $parts */
        $parts = array_values(array_filter([$host, $path], static fn (mixed $s): bool => $s !== ''));
        $segments = implode('.', array_map(
            static fn (mixed $p): string => str_replace(['/', '-', '_'], '.', (string) $p),
            $parts,
        ));

        return 'g.' . $segments;
    }
}
