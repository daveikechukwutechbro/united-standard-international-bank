<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\ValueObjects;

use App\Domain\ISO20022\Enums\MessageFamily;
use DateTimeImmutable;
use Illuminate\Support\Str;

final readonly class BusinessApplicationHeader
{
    public function __construct(
        public string $businessMessageId,
        public string $messageDefinitionId,
        public string $from,
        public string $to,
        public DateTimeImmutable $creationDate,
        public ?string $uetr = null,
    ) {
    }

    public static function create(
        string $messageDefinitionId,
        string $from,
        string $to,
        ?string $uetr = null,
    ): self {
        return new self(
            businessMessageId: Str::uuid()->toString(),
            messageDefinitionId: $messageDefinitionId,
            from: $from,
            to: $to,
            creationDate: new DateTimeImmutable(),
            uetr: $uetr ?? (config('iso20022.uetr_enabled') ? Str::uuid()->toString() : null),
        );
    }

    public function family(): MessageFamily
    {
        $prefix = explode('.', $this->messageDefinitionId)[0] ?? '';

        return MessageFamily::from($prefix);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'business_message_id'   => $this->businessMessageId,
            'message_definition_id' => $this->messageDefinitionId,
            'from'                  => $this->from,
            'to'                    => $this->to,
            'creation_date'         => $this->creationDate->format('Y-m-d\TH:i:s.vP'),
            'uetr'                  => $this->uetr,
        ];
    }
}
