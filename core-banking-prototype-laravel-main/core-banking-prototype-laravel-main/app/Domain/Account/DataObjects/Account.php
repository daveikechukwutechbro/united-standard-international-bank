<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Account extends DataObject implements DataObjectContract
{
    public function __construct(
        private string $name,
        private string $userUuid,
        private ?string $uuid = null
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function withUuid(string $uuid): self
    {
        return new self(
            name: $this->name,
            userUuid: $this->userUuid,
            uuid: $uuid,
        );
    }

    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'user_uuid' => $this->userUuid,
            'uuid'      => $this->uuid,
        ];
    }
}
