<?php

declare(strict_types=1);

namespace App\Domain\X402\DataObjects;

/**
 * Describes the resource being monetized via x402.
 *
 * Contains the URL of the protected endpoint, a human-readable description,
 * and the MIME type of the response the caller will receive upon successful payment.
 */
readonly class ResourceInfo
{
    public function __construct(
        public string $url,
        public string $description,
        public string $mimeType,
    ) {
    }

    /**
     * Serialize to a plain array.
     *
     * @return array{url: string, description: string, mimeType: string}
     */
    public function toArray(): array
    {
        return [
            'url'         => $this->url,
            'description' => $this->description,
            'mimeType'    => $this->mimeType,
        ];
    }

    /**
     * Hydrate from a plain array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['url', 'description', 'mimeType'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw \App\Domain\X402\Exceptions\X402InvalidPayloadException::missingField($field);
            }
        }

        return new self(
            url: $data['url'],
            description: $data['description'],
            mimeType: $data['mimeType'],
        );
    }
}
