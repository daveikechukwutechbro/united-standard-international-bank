<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\ValueObjects;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;

final class Iso8583Message
{
    private Bitmap $bitmap;

    /** @var array<int, DataElement> */
    private array $dataElements = [];

    public function __construct(
        private readonly MessageTypeIndicator $mti,
    ) {
        $this->bitmap = new Bitmap();
    }

    public function setField(int $number, string $value): self
    {
        $this->bitmap->setField($number);
        $this->dataElements[$number] = new DataElement(
            fieldNumber: $number,
            value: $value,
        );

        return $this;
    }

    public function getField(int $number): ?string
    {
        return isset($this->dataElements[$number]) ? $this->dataElements[$number]->value : null;
    }

    public function getMti(): MessageTypeIndicator
    {
        return $this->mti;
    }

    public function getBitmap(): Bitmap
    {
        return $this->bitmap;
    }

    /** @return array<int> */
    public function presentFields(): array
    {
        return $this->bitmap->presentFields();
    }

    /** @return array<int, DataElement> */
    public function getDataElements(): array
    {
        return $this->dataElements;
    }
}
