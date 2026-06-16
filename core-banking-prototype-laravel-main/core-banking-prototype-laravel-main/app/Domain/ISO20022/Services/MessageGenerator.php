<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Services;

use RuntimeException;

final class MessageGenerator
{
    public function __construct(
        private readonly MessageRegistry $registry,
    ) {
    }

    public function toXml(object $dto): string
    {
        if (! method_exists($dto, 'toXml')) {
            throw new RuntimeException(
                sprintf('DTO class %s does not implement toXml()', $dto::class)
            );
        }

        return $dto->toXml();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generateFromArray(string $messageType, array $data): string
    {
        $dtoClass = $this->registry->getDtoClass($messageType);

        if ($dtoClass === null) {
            throw new RuntimeException("Unsupported message type: {$messageType}");
        }

        /** @var object $dto */
        $dto = $dtoClass::fromArray($data);

        return $this->toXml($dto);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $dto): array
    {
        if (! method_exists($dto, 'toArray')) {
            throw new RuntimeException(
                sprintf('DTO class %s does not implement toArray()', $dto::class)
            );
        }

        /** @var array<string, mixed> $result */
        $result = $dto->toArray();

        return $result;
    }
}
