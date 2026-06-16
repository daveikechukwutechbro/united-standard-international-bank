<?php

declare(strict_types=1);

namespace App\Domain\X402\Contracts;

use App\Domain\X402\DataObjects\PaymentPayload;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\SettleResponse;
use App\Domain\X402\DataObjects\VerifyResponse;

/**
 * Client interface for communicating with an x402 facilitator.
 *
 * The facilitator is responsible for verifying payment signatures and
 * settling (executing) the on-chain transfer on behalf of the resource server.
 */
interface FacilitatorClientInterface
{
    /**
     * Verify that a payment payload is cryptographically valid and satisfies the requirements.
     *
     * The facilitator checks the signature, nonce, expiry, amount, and recipient
     * without executing any on-chain transaction.
     */
    public function verify(PaymentPayload $payload, PaymentRequirements $requirements): VerifyResponse;

    /**
     * Settle a verified payment by executing the on-chain transfer.
     *
     * Should only be called after a successful verify().  The facilitator submits
     * the signed authorization to the blockchain and returns the transaction hash.
     */
    public function settle(PaymentPayload $payload, PaymentRequirements $requirements): SettleResponse;

    /**
     * List the networks and assets supported by this facilitator.
     *
     * @return array<string, array<string, mixed>> Keyed by CAIP-2 network identifier.
     */
    public function supported(): array;
}
