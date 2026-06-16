<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\ValueObjects;

use InvalidArgumentException;

final class Bitmap
{
    /** @var array<int, bool> */
    private array $fields = [];

    public function setField(int $fieldNumber): self
    {
        if ($fieldNumber < 1 || $fieldNumber > 128) {
            throw new InvalidArgumentException("Field number must be 1-128, got {$fieldNumber}");
        }
        $this->fields[$fieldNumber] = true;

        return $this;
    }

    public function hasField(int $fieldNumber): bool
    {
        return $this->fields[$fieldNumber] ?? false;
    }

    public function hasSecondaryBitmap(): bool
    {
        foreach ($this->fields as $field => $present) {
            if ($present && $field > 64) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int> */
    public function presentFields(): array
    {
        $fields = array_keys(array_filter($this->fields));
        sort($fields);

        return $fields;
    }

    public function encode(): string
    {
        $bytes = $this->hasSecondaryBitmap() ? 16 : 8;
        $bitmap = str_repeat("\x00", $bytes);

        foreach ($this->fields as $field => $present) {
            if (! $present) {
                continue;
            }
            $byteIndex = (int) (($field - 1) / 8);
            $bitIndex = 7 - (($field - 1) % 8);
            $bitmap[$byteIndex] = chr(ord($bitmap[$byteIndex]) | (1 << $bitIndex));
        }

        if ($this->hasSecondaryBitmap()) {
            $bitmap[0] = chr(ord($bitmap[0]) | 0x80);
        }

        return bin2hex($bitmap);
    }

    public static function decode(string $hex): self
    {
        $bitmap = new self();
        $bytes = hex2bin($hex);
        if ($bytes === false) {
            throw new InvalidArgumentException('Invalid hex bitmap');
        }

        $totalBits = strlen($bytes) * 8;
        for ($i = 0; $i < $totalBits; $i++) {
            $byteIndex = (int) ($i / 8);
            $bitIndex = 7 - ($i % 8);
            if (ord($bytes[$byteIndex]) & (1 << $bitIndex)) {
                $bitmap->setField($i + 1);
            }
        }

        return $bitmap;
    }
}
