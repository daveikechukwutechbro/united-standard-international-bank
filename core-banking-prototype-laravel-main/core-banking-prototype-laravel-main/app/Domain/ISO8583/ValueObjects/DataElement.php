<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\ValueObjects;

final readonly class DataElement
{
    public function __construct(
        public int $fieldNumber,
        public string $value,
        public string $type = 'FIXED',
        public int $maxLength = 0,
    ) {
    }
}
