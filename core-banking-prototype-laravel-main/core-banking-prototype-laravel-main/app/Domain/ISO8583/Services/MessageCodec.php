<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Services;

use App\Domain\ISO8583\Enums\MessageTypeIndicator;
use App\Domain\ISO8583\ValueObjects\Bitmap;
use App\Domain\ISO8583\ValueObjects\Iso8583Message;
use RuntimeException;

final class MessageCodec
{
    public function __construct(
        private readonly FieldDefinitions $fieldDefinitions,
    ) {
    }

    public function encode(Iso8583Message $message): string
    {
        $result = $message->getMti()->value;
        $bitmap = $message->getBitmap();
        $result .= $bitmap->encode();

        foreach ($bitmap->presentFields() as $fieldNum) {
            $value = $message->getField($fieldNum);
            if ($value === null) {
                continue;
            }
            $def = $this->fieldDefinitions->getField($fieldNum);
            if ($def === null) {
                throw new RuntimeException("Unknown field definition for field {$fieldNum}");
            }
            $result .= $this->encodeField($value, $def);
        }

        return $result;
    }

    public function decode(string $raw): Iso8583Message
    {
        $offset = 0;

        // Read MTI (4 characters)
        $mtiValue = substr($raw, $offset, 4);
        $offset += 4;
        $mti = MessageTypeIndicator::from($mtiValue);

        // Read primary bitmap (16 hex chars = 8 bytes)
        $bitmapHex = substr($raw, $offset, 16);
        $offset += 16;

        // Check if secondary bitmap present (first bit set = first hex char >= 8)
        $firstNibble = hexdec($bitmapHex[0]);
        if ($firstNibble >= 8) {
            $bitmapHex .= substr($raw, $offset, 16);
            $offset += 16;
        }

        $bitmap = Bitmap::decode($bitmapHex);
        $message = new Iso8583Message($mti);

        foreach ($bitmap->presentFields() as $fieldNum) {
            if ($fieldNum === 1) {
                continue; // Field 1 is the secondary bitmap indicator
            }
            $def = $this->fieldDefinitions->getField($fieldNum);
            if ($def === null) {
                throw new RuntimeException("Unknown field definition for field {$fieldNum}");
            }
            $decoded = $this->decodeField($raw, $offset, $def);
            $message->setField($fieldNum, $decoded['value']);
            $offset = $decoded['offset'];
        }

        return $message;
    }

    /**
     * @param array{name: string, type: string, max_length: int} $def
     */
    private function encodeField(string $value, array $def): string
    {
        return match ($def['type']) {
            'N'      => str_pad($value, $def['max_length'], '0', STR_PAD_LEFT),
            'AN'     => str_pad($value, $def['max_length'], ' ', STR_PAD_RIGHT),
            'LLVAR'  => str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value,
            'LLLVAR' => str_pad((string) strlen($value), 3, '0', STR_PAD_LEFT) . $value,
            default  => $value,
        };
    }

    /**
     * @param array{name: string, type: string, max_length: int} $def
     * @return array{value: string, offset: int}
     */
    private function decodeField(string $raw, int $offset, array $def): array
    {
        return match ($def['type']) {
            'N', 'AN' => [
                'value'  => trim(substr($raw, $offset, $def['max_length'])),
                'offset' => $offset + $def['max_length'],
            ],
            'LLVAR'  => $this->decodeVarField($raw, $offset, 2),
            'LLLVAR' => $this->decodeVarField($raw, $offset, 3),
            default  => ['value' => '', 'offset' => $offset],
        };
    }

    /**
     * @return array{value: string, offset: int}
     */
    private function decodeVarField(string $raw, int $offset, int $lengthDigits): array
    {
        $length = (int) substr($raw, $offset, $lengthDigits);
        $offset += $lengthDigits;
        $value = substr($raw, $offset, $length);

        return ['value' => $value, 'offset' => $offset + $length];
    }
}
