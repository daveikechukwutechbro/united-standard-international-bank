<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Services;

final class FieldDefinitions
{
    /**
     * Field definitions: number => [name, type, max_length]
     * Types: N=Numeric fixed, AN=Alphanumeric fixed, LLVAR=variable (2-digit length prefix), LLLVAR=variable (3-digit length prefix).
     *
     * @return array{name: string, type: string, max_length: int}|null
     */
    public function getField(int $number): ?array
    {
        return self::FIELDS[$number] ?? null;
    }

    /** @return array<int, array{name: string, type: string, max_length: int}> */
    public function all(): array
    {
        return self::FIELDS;
    }

    /** @var array<int, array{name: string, type: string, max_length: int}> */
    private const FIELDS = [
        2  => ['name' => 'Primary Account Number', 'type' => 'LLVAR', 'max_length' => 19],
        3  => ['name' => 'Processing Code', 'type' => 'N', 'max_length' => 6],
        4  => ['name' => 'Amount Transaction', 'type' => 'N', 'max_length' => 12],
        7  => ['name' => 'Transmission Date Time', 'type' => 'N', 'max_length' => 10],
        11 => ['name' => 'System Trace Audit Number', 'type' => 'N', 'max_length' => 6],
        12 => ['name' => 'Local Transaction Time', 'type' => 'N', 'max_length' => 6],
        13 => ['name' => 'Local Transaction Date', 'type' => 'N', 'max_length' => 4],
        14 => ['name' => 'Expiration Date', 'type' => 'N', 'max_length' => 4],
        22 => ['name' => 'POS Entry Mode', 'type' => 'N', 'max_length' => 3],
        23 => ['name' => 'Card Sequence Number', 'type' => 'N', 'max_length' => 3],
        25 => ['name' => 'POS Condition Code', 'type' => 'N', 'max_length' => 2],
        26 => ['name' => 'POS PIN Capture Code', 'type' => 'N', 'max_length' => 2],
        32 => ['name' => 'Acquiring Institution ID', 'type' => 'LLVAR', 'max_length' => 11],
        35 => ['name' => 'Track 2 Data', 'type' => 'LLVAR', 'max_length' => 37],
        37 => ['name' => 'Retrieval Reference Number', 'type' => 'AN', 'max_length' => 12],
        38 => ['name' => 'Authorization Code', 'type' => 'AN', 'max_length' => 6],
        39 => ['name' => 'Response Code', 'type' => 'AN', 'max_length' => 2],
        41 => ['name' => 'Card Acceptor Terminal ID', 'type' => 'AN', 'max_length' => 8],
        42 => ['name' => 'Card Acceptor ID', 'type' => 'AN', 'max_length' => 15],
        43 => ['name' => 'Card Acceptor Name/Location', 'type' => 'AN', 'max_length' => 40],
        49 => ['name' => 'Currency Code Transaction', 'type' => 'N', 'max_length' => 3],
        54 => ['name' => 'Additional Amounts', 'type' => 'LLLVAR', 'max_length' => 120],
        55 => ['name' => 'ICC System Related Data', 'type' => 'LLLVAR', 'max_length' => 999],
        60 => ['name' => 'Private Use', 'type' => 'LLLVAR', 'max_length' => 60],
        70 => ['name' => 'Network Management Code', 'type' => 'N', 'max_length' => 3],
    ];
}
