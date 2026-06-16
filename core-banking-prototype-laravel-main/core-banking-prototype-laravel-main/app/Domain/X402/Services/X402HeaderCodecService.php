<?php

declare(strict_types=1);

namespace App\Domain\X402\Services;

use App\Domain\X402\DataObjects\PaymentPayload;
use App\Domain\X402\DataObjects\PaymentRequired;
use App\Domain\X402\DataObjects\SettleResponse;
use App\Domain\X402\Exceptions\X402InvalidPayloadException;
use JsonException;

class X402HeaderCodecService
{
    /** Maximum allowed Base64 header size (~8KB encoded = ~6KB decoded). */
    private const MAX_HEADER_SIZE = 8192;

    /**
     * Encode a PaymentRequired object for the PAYMENT-REQUIRED response header.
     */
    public function encodePaymentRequired(PaymentRequired $paymentRequired): string
    {
        return base64_encode(json_encode($paymentRequired->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * Decode a PAYMENT-SIGNATURE header into a PaymentPayload.
     *
     * @throws X402InvalidPayloadException
     */
    public function decodePaymentPayload(string $header): PaymentPayload
    {
        if (strlen($header) > self::MAX_HEADER_SIZE) {
            throw X402InvalidPayloadException::invalidBase64('PAYMENT-SIGNATURE header exceeds maximum allowed size');
        }

        $decoded = base64_decode($header, true);
        if ($decoded === false) {
            throw X402InvalidPayloadException::invalidBase64('Failed to decode PAYMENT-SIGNATURE header');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw X402InvalidPayloadException::invalidBase64('Invalid JSON in PAYMENT-SIGNATURE: ' . $e->getMessage());
        }

        return PaymentPayload::fromArray($data);
    }

    /**
     * Encode a SettleResponse for the PAYMENT-RESPONSE header.
     */
    public function encodeSettleResponse(SettleResponse $settleResponse): string
    {
        return base64_encode(json_encode($settleResponse->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * Decode a PAYMENT-REQUIRED header from an external 402 response.
     *
     * @throws X402InvalidPayloadException
     */
    public function decodePaymentRequired(string $header): PaymentRequired
    {
        if (strlen($header) > self::MAX_HEADER_SIZE) {
            throw X402InvalidPayloadException::invalidBase64('PAYMENT-REQUIRED header exceeds maximum allowed size');
        }

        $decoded = base64_decode($header, true);
        if ($decoded === false) {
            throw X402InvalidPayloadException::invalidBase64('Failed to decode PAYMENT-REQUIRED header');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw X402InvalidPayloadException::invalidBase64('Invalid JSON in PAYMENT-REQUIRED: ' . $e->getMessage());
        }

        return PaymentRequired::fromArray($data);
    }
}
