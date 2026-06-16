<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Services;

/**
 * Parses ACH return and NOC (Notification of Change) files.
 *
 * Return file structure:
 *   Record type 1 — File Header
 *   Record type 5 — Batch Header
 *   Record type 6 — Entry Detail (original transaction info)
 *   Record type 7 — Addenda Record (contains return code Rxxx)
 *   Record type 8 — Batch Control
 *   Record type 9 — File Control
 *   Padding records (all 9s)
 */
final class NachaFileParser
{
    /**
     * Parse a full NACHA file into a structured array.
     *
     * @return array{
     *     file_header: array<string, string>,
     *     batches: array<int, array{
     *         batch_header: array<string, string>,
     *         entries: array<int, array<string, string>>,
     *         addenda: array<int, array<string, string>>,
     *         batch_control: array<string, string>
     *     }>,
     *     file_control: array<string, string>
     * }
     */
    public function parse(string $fileContent): array
    {
        $lines = $this->splitLines($fileContent);
        $result = [
            'file_header'  => [],
            'batches'      => [],
            'file_control' => [],
        ];

        $currentBatch = null;

        foreach ($lines as $line) {
            if (strlen($line) < 1) {
                continue;
            }

            $recordType = $line[0];

            switch ($recordType) {
                case '1':
                    $result['file_header'] = $this->parseFileHeader($line);
                    break;

                case '5':
                    $currentBatch = [
                        'batch_header'  => $this->parseBatchHeader($line),
                        'entries'       => [],
                        'addenda'       => [],
                        'batch_control' => [],
                    ];
                    break;

                case '6':
                    if ($currentBatch !== null) {
                        $currentBatch['entries'][] = $this->parseEntryDetail($line);
                    }
                    break;

                case '7':
                    if ($currentBatch !== null) {
                        $currentBatch['addenda'][] = $this->parseAddenda($line);
                    }
                    break;

                case '8':
                    if ($currentBatch !== null) {
                        $currentBatch['batch_control'] = $this->parseBatchControl($line);
                        $result['batches'][] = $currentBatch;
                        $currentBatch = null;
                    }
                    break;

                case '9':
                    // File control or padding (all 9s)
                    if (trim($line) !== str_repeat('9', strlen(trim($line)))) {
                        $result['file_control'] = $this->parseFileControl($line);
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * Extract return entries from a return ACH file.
     *
     * @return array<int, array{
     *     trace_number: string,
     *     return_code: string,
     *     original_entry: array<string, string>
     * }>
     */
    public function parseReturnEntries(string $fileContent): array
    {
        $parsed = $this->parse($fileContent);
        $returns = [];

        foreach ($parsed['batches'] as $batch) {
            foreach ($batch['entries'] as $entryIndex => $entry) {
                // Find matching addenda record for this entry
                $addenda = $batch['addenda'][$entryIndex] ?? null;
                $returnCode = $addenda !== null ? $this->extractReturnCode($this->buildAddendaLine($addenda)) : null;

                if ($returnCode === null) {
                    continue;
                }

                $returns[] = [
                    'trace_number'   => trim($entry['trace_number'] ?? ''),
                    'return_code'    => $returnCode,
                    'original_entry' => $entry,
                ];
            }
        }

        return $returns;
    }

    /**
     * Extract R-code from a 94-character addenda record (type 7).
     *
     * Addenda record layout:
     *   Pos  1:     Record Type (7)
     *   Pos  2-3:   Addenda Type Code (99 = return)
     *   Pos  4-6:   Return Reason Code (e.g. R01)
     *   Pos  7-21:  Original Entry Trace Number
     *   Pos  22-27: Date of Death (optional, spaces if not applicable)
     *   Pos  28-39: Original Receiving DFI
     *   Pos  40-94: Addenda Information
     */
    public function extractReturnCode(string $addendaRecord): ?string
    {
        if (strlen($addendaRecord) < 6) {
            return null;
        }

        $addendaType = substr($addendaRecord, 1, 2);

        // Only type 99 (return) and 98 (NOC) carry R-codes
        if (! in_array($addendaType, ['99', '98'], true)) {
            return null;
        }

        $returnCode = trim(substr($addendaRecord, 3, 3));

        // Validate format: R followed by two digits
        if (! preg_match('/^R\d{2}$/', $returnCode)) {
            return null;
        }

        return $returnCode;
    }

    // ── Record parsers ───────────────────────────────────────────────────────

    /** @return array<string, string> */
    private function parseFileHeader(string $line): array
    {
        return [
            'record_type'        => substr($line, 0, 1),
            'priority_code'      => substr($line, 1, 2),
            'immediate_dest'     => trim(substr($line, 3, 10)),
            'immediate_origin'   => trim(substr($line, 13, 10)),
            'file_creation_date' => substr($line, 23, 6),
            'file_creation_time' => substr($line, 29, 4),
            'file_id_modifier'   => substr($line, 33, 1),
            'record_size'        => substr($line, 34, 3),
            'blocking_factor'    => substr($line, 37, 2),
            'format_code'        => substr($line, 39, 1),
            'dest_name'          => trim(substr($line, 40, 23)),
            'origin_name'        => trim(substr($line, 63, 23)),
            'reference_code'     => trim(substr($line, 86, 8)),
        ];
    }

    /** @return array<string, string> */
    private function parseBatchHeader(string $line): array
    {
        return [
            'record_type'          => substr($line, 0, 1),
            'service_class_code'   => substr($line, 1, 3),
            'company_name'         => trim(substr($line, 4, 16)),
            'company_id'           => trim(substr($line, 20, 10)),
            'sec_code'             => trim(substr($line, 30, 10)),
            'company_entry_desc'   => trim(substr($line, 40, 20)),
            'effective_entry_date' => substr($line, 60, 6),
            'settlement_date'      => substr($line, 66, 3),
            'originator_status'    => substr($line, 69, 1),
            'originating_dfi'      => substr($line, 70, 8),
            'batch_number'         => trim(substr($line, 78, 7)),
        ];
    }

    /** @return array<string, string> */
    private function parseEntryDetail(string $line): array
    {
        return [
            'record_type'        => substr($line, 0, 1),
            'transaction_code'   => substr($line, 1, 2),
            'routing_number'     => substr($line, 3, 8),
            'check_digit'        => substr($line, 11, 1),
            'account_number'     => trim(substr($line, 12, 17)),
            'amount'             => substr($line, 29, 10),
            'individual_id'      => trim(substr($line, 39, 15)),
            'individual_name'    => trim(substr($line, 54, 22)),
            'discretionary_data' => substr($line, 76, 2),
            'addenda_indicator'  => substr($line, 78, 1),
            'trace_number'       => substr($line, 79, 15),
        ];
    }

    /** @return array<string, string> */
    private function parseAddenda(string $line): array
    {
        return [
            'record_type'         => substr($line, 0, 1),
            'addenda_type_code'   => substr($line, 1, 2),
            'return_reason_code'  => trim(substr($line, 3, 3)),
            'original_trace'      => trim(substr($line, 6, 15)),
            'date_of_death'       => trim(substr($line, 21, 6)),
            'original_dfi'        => trim(substr($line, 27, 12)),
            'addenda_information' => trim(substr($line, 39, 55)),
        ];
    }

    /** @return array<string, string> */
    private function parseBatchControl(string $line): array
    {
        return [
            'record_type'         => substr($line, 0, 1),
            'service_class_code'  => substr($line, 1, 3),
            'entry_addenda_count' => trim(substr($line, 4, 6)),
            'entry_hash'          => trim(substr($line, 10, 10)),
            'total_debit'         => trim(substr($line, 20, 12)),
            'total_credit'        => trim(substr($line, 32, 12)),
            'company_id'          => trim(substr($line, 44, 10)),
            'message_auth_code'   => trim(substr($line, 54, 19)),
            'reserved'            => substr($line, 73, 6),
            'originating_dfi'     => substr($line, 79, 8),
            'batch_number'        => trim(substr($line, 87, 7)),
        ];
    }

    /** @return array<string, string> */
    private function parseFileControl(string $line): array
    {
        return [
            'record_type'         => substr($line, 0, 1),
            'batch_count'         => trim(substr($line, 1, 6)),
            'block_count'         => trim(substr($line, 7, 6)),
            'entry_addenda_count' => trim(substr($line, 13, 8)),
            'entry_hash'          => trim(substr($line, 21, 10)),
            'total_debit'         => trim(substr($line, 31, 12)),
            'total_credit'        => trim(substr($line, 43, 12)),
            'reserved'            => substr($line, 55, 39),
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Split file content into individual 94-character records.
     * Handles both CRLF and LF line endings, and also fixed-width files
     * without line endings.
     *
     * @return list<string>
     */
    private function splitLines(string $fileContent): array
    {
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $fileContent);
        $content = str_replace("\r", "\n", $content);

        if (str_contains($content, "\n")) {
            return array_values(
                array_filter(explode("\n", $content), fn (string $l): bool => $l !== ''),
            );
        }

        // Fixed-width format (no newlines) — split by record length
        $lines = [];
        $len = strlen($content);
        for ($i = 0; $i < $len; $i += 94) {
            $line = substr($content, $i, 94);
            if (strlen($line) > 0) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Reconstruct a 94-char addenda line from its parsed array, for extractReturnCode().
     *
     * @param array<string, string> $addenda
     */
    private function buildAddendaLine(array $addenda): string
    {
        return '7'
            . ($addenda['addenda_type_code'] ?? '  ')
            . ($addenda['return_reason_code'] ?? '   ')
            . str_repeat(' ', 88); // remainder not needed for code extraction
    }
}
