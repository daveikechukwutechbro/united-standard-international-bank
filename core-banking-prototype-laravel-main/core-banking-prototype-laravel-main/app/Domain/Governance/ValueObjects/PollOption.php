<?php

declare(strict_types=1);

namespace App\Domain\Governance\ValueObjects;

final readonly class PollOption
{
    public function __construct(
        public string $id,
        public string $label,
        public ?string $description = null,
        public ?array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            label: $data['label'],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'label'       => $this->label,
            'description' => $this->description,
            'metadata'    => $this->metadata,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
