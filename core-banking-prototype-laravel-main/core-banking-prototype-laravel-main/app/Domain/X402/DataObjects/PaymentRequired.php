<?php

declare(strict_types=1);

namespace App\Domain\X402\DataObjects;

use App\Domain\X402\Exceptions\X402InvalidPayloadException;

/**
 * The 402 Payment Required response body.
 *
 * Returned by a monetized endpoint when the client has not provided a valid
 * payment header.  Contains the protocol version, resource info, and one or
 * more payment options (accepts) that the client may satisfy.
 */
readonly class PaymentRequired
{
    /**
     * @param int                       $x402Version  Protocol version (currently 2).
     * @param ResourceInfo              $resource     Metadata about the protected resource.
     * @param array<PaymentRequirements> $accepts     Acceptable payment options.
     * @param string|null               $error        Optional error description.
     * @param array<string, mixed>|null $extensions   Optional protocol extensions.
     */
    public function __construct(
        public int $x402Version,
        public ResourceInfo $resource,
        public array $accepts,
        public ?string $error = null,
        public ?array $extensions = null,
    ) {
    }

    /**
     * Serialize to a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'x402Version' => $this->x402Version,
            'resource'    => $this->resource->toArray(),
            'accepts'     => array_map(
                static fn (PaymentRequirements $r): array => $r->toArray(),
                $this->accepts,
            ),
            'error'      => $this->error,
            'extensions' => $this->extensions,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Hydrate from a plain array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['x402Version', 'resource', 'accepts'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw X402InvalidPayloadException::missingField($field);
            }
        }

        return new self(
            x402Version: $data['x402Version'],
            resource: ResourceInfo::fromArray($data['resource']),
            accepts: array_map(
                static fn (array $item): PaymentRequirements => PaymentRequirements::fromArray($item),
                $data['accepts'],
            ),
            error: $data['error'] ?? null,
            extensions: $data['extensions'] ?? null,
        );
    }

    /**
     * Encode the payment-required payload as a base64 JSON string.
     */
    public function toBase64(): string
    {
        return base64_encode((string) json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * Decode a base64-encoded payment-required payload.
     *
     * @throws X402InvalidPayloadException
     */
    public static function fromBase64(string $encoded): self
    {
        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            throw X402InvalidPayloadException::invalidBase64('Failed to decode PaymentRequired base64 string.');
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($decoded, true);

        if (! is_array($data)) {
            throw X402InvalidPayloadException::invalidBase64('PaymentRequired base64 payload is not valid JSON.');
        }

        return self::fromArray($data);
    }
}
