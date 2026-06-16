<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Services;

use App\Domain\PaymentRails\Models\AchBatch;
use App\Domain\PaymentRails\Models\AchEntry;
use Illuminate\Support\Carbon;

/**
 * Generates NACHA-compliant ACH batch files.
 *
 * Every record is exactly 94 characters wide, right-padded with spaces (alpha)
 * or left-padded with zeros (numeric).  Files are blocked to multiples of 10
 * records (9-padded) as required by the NACHA standard.
 */
final class NachaFileGenerator
{
    private const RECORD_LENGTH = 94;

    private const BLOCK_SIZE = 10;

    /** Generate a complete NACHA file for the given batch. */
    public function generate(AchBatch $batch): string
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AchEntry> $entries */
        $entries = $batch->entries()->get();

        $lines = [];
        $lines[] = $this->generateFileHeader();
        $lines[] = $this->generateBatchHeader($batch);

        $traceSeq = 1;
        foreach ($entries as $entry) {
            $lines[] = $this->generateEntryDetail($entry, $traceSeq++);
        }

        $batchEntryHash = $this->computeEntryHash($entries);
        $lines[] = $this->generateBatchControl($batch);

        // File control totals
        $totalDebit = $this->formatAmount((string) $batch->total_debit);
        $totalCredit = $this->formatAmount((string) $batch->total_credit);
        $lines[] = $this->generateFileControl(1, $batch->entry_count, $batchEntryHash, $totalDebit, $totalCredit);

        // Pad file to block boundary (multiples of 10 records)
        $totalRecords = count($lines);
        $paddingNeeded = (self::BLOCK_SIZE - ($totalRecords % self::BLOCK_SIZE)) % self::BLOCK_SIZE;
        for ($i = 0; $i < $paddingNeeded; $i++) {
            $lines[] = str_repeat('9', self::RECORD_LENGTH);
        }

        return implode("\n", $lines);
    }

    /**
     * Record type 1 — File Header.
     *
     * Priority Code:         01
     * Immediate Destination: originating_dfi (space-prefixed to 10 chars)
     * Immediate Origin:      company_id (space-prefixed to 10 chars)
     * File Creation Date:    YYMMDD
     * File Creation Time:    HHMM
     * File ID Modifier:      A
     * Record Size:           094
     * Blocking Factor:       10
     * Format Code:           1
     */
    public function generateFileHeader(): string
    {
        $now = Carbon::now();
        $destination = str_pad((string) config('payment_rails.ach.originating_dfi', ''), 9, '0', STR_PAD_LEFT);
        $origin = str_pad((string) config('payment_rails.ach.company_id', ''), 10, ' ', STR_PAD_LEFT);
        $destName = str_pad('FEDERAL RESERVE', 23, ' ');
        $originName = str_pad((string) config('payment_rails.ach.company_name', 'FinAegis'), 23, ' ');
        $reference = str_pad('', 8, ' ');

        $record =
            '1'                              // Record Type Code
            . '01'                           // Priority Code
            . ' ' . $destination            // Immediate Destination (routing + check)
            . $origin                        // Immediate Origin
            . $now->format('ymd')           // File Creation Date YYMMDD
            . $now->format('Hi')            // File Creation Time HHMM
            . 'A'                            // File ID Modifier
            . '094'                          // Record Size
            . '10'                           // Blocking Factor
            . '1'                            // Format Code
            . $destName                      // Immediate Destination Name
            . $originName                    // Immediate Origin Name
            . $reference;                    // Reference Code

        return $this->pad($record);
    }

    /**
     * Record type 5 — Batch Header.
     *
     * Service Class Code: 200=mixed, 220=credit, 225=debit
     * Company Name:       10 chars right-padded
     * Company Entry Desc: 10 chars right-padded
     * Effective Entry Date: YYMMDD
     * Originator Status Code: 1
     * Originating DFI: 8 chars
     * Batch Number: 7 digits
     */
    public function generateBatchHeader(AchBatch $batch): string
    {
        $companyName = str_pad(substr((string) config('payment_rails.ach.company_name', 'FinAegis'), 0, 16), 16, ' ');
        $companyId = str_pad(substr((string) config('payment_rails.ach.company_id', ''), 0, 10), 10, ' ');
        $entryDesc = str_pad(strtoupper($batch->sec_code), 10, ' ');
        $companyDesc = str_pad('', 20, ' ');
        $effectiveDate = $batch->settlement_date
            ? Carbon::parse($batch->settlement_date)->format('ymd')
            : Carbon::now()->addDay()->format('ymd');
        $settlementDate = '   '; // bank-filled
        $origDfi = str_pad(substr((string) config('payment_rails.ach.originating_dfi', ''), 0, 8), 8, '0', STR_PAD_LEFT);
        $batchNumber = str_pad('1', 7, '0', STR_PAD_LEFT);

        // Service class: 220 = credits only, 225 = debits only, 200 = mixed
        $serviceClass = '200';

        $record =
            '5'
            . $serviceClass
            . $companyName
            . $companyId
            . $entryDesc
            . $companyDesc
            . $effectiveDate
            . $settlementDate
            . '1'            // Originator Status Code
            . $origDfi
            . $batchNumber;

        return $this->pad($record);
    }

    /**
     * Record type 6 — Entry Detail.
     *
     * Pos  1:      Record Type Code (6)
     * Pos  2-3:    Transaction Code
     * Pos  4-11:   Routing Number (8 digits, no check digit)
     * Pos  12:     Check Digit
     * Pos  13-29:  Account Number (17 chars left-justified)
     * Pos  30-39:  Amount (10 digits, cents, no decimal)
     * Pos  40-54:  Individual ID Number (15 chars)
     * Pos  55-76:  Individual Name (22 chars)
     * Pos  77-78:  Discretionary Data (2 chars)
     * Pos  79:     Addenda Record Indicator (0 or 1)
     * Pos  80-94:  Trace Number (15 digits)
     */
    public function generateEntryDetail(AchEntry $entry, int $traceSequence): string
    {
        $routing = str_pad($entry->routing_number, 9, '0', STR_PAD_LEFT);
        $routingBody = substr($routing, 0, 8);
        $checkDigit = substr($routing, 8, 1);

        $account = str_pad(substr($entry->account_number, 0, 17), 17, ' ');
        $amountStr = str_pad((string) $this->toCents($entry->amount), 10, '0', STR_PAD_LEFT);

        $individualId = str_pad(substr((string) ($entry->individual_id ?? ''), 0, 15), 15, ' ');
        $individualName = str_pad(substr($entry->individual_name, 0, 22), 22, ' ');

        $origDfi = str_pad(substr((string) config('payment_rails.ach.originating_dfi', ''), 0, 8), 8, '0', STR_PAD_LEFT);
        $traceNum = $origDfi . str_pad((string) $traceSequence, 7, '0', STR_PAD_LEFT);

        $record =
            '6'
            . $entry->transaction_code          // 2 chars
            . $routingBody                       // 8 chars
            . $checkDigit                        // 1 char
            . $account                           // 17 chars
            . $amountStr                         // 10 chars
            . $individualId                      // 15 chars
            . $individualName                    // 22 chars
            . '  '                               // Discretionary Data (2 chars)
            . '0'                                // Addenda Record Indicator
            . $traceNum;                         // 15 chars

        return $this->pad($record);
    }

    /**
     * Record type 8 — Batch Control.
     *
     * Service Class Code: 200
     * Entry/Addenda Count: 6 digits
     * Entry Hash: 10 digits (sum of routing numbers, last 10 digits)
     * Total Debit Amount: 12 digits
     * Total Credit Amount: 12 digits
     * Company ID: 10 chars
     * Message Auth Code: 19 spaces (bank-filled)
     * Reserved: 6 spaces
     * Originating DFI: 8 chars
     * Batch Number: 7 digits
     */
    public function generateBatchControl(AchBatch $batch): string
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, AchEntry> $entries */
        $entries = $batch->entries()->get();
        $entryHash = $this->computeEntryHash($entries);
        $debitAmt = str_pad((string) $this->toCents($batch->total_debit), 12, '0', STR_PAD_LEFT);
        $creditAmt = str_pad((string) $this->toCents($batch->total_credit), 12, '0', STR_PAD_LEFT);
        $companyId = str_pad(substr((string) config('payment_rails.ach.company_id', ''), 0, 10), 10, ' ');
        $origDfi = str_pad(substr((string) config('payment_rails.ach.originating_dfi', ''), 0, 8), 8, '0', STR_PAD_LEFT);
        $batchNum = str_pad('1', 7, '0', STR_PAD_LEFT);
        $entryCount = str_pad((string) $batch->entry_count, 6, '0', STR_PAD_LEFT);

        $record =
            '8'
            . '200'
            . $entryCount
            . $entryHash
            . $debitAmt
            . $creditAmt
            . $companyId
            . str_repeat(' ', 19)   // Message Auth Code
            . str_repeat(' ', 6)    // Reserved
            . $origDfi
            . $batchNum;

        return $this->pad($record);
    }

    /**
     * Record type 9 — File Control.
     *
     * Batch Count: 6 digits
     * Block Count: 6 digits (total records / 10, rounded up)
     * Entry/Addenda Count: 8 digits
     * Entry Hash: 10 digits
     * Total Debit: 12 digits
     * Total Credit: 12 digits
     * Reserved: 39 spaces
     */
    public function generateFileControl(
        int $batchCount,
        int $entryCount,
        string $entryHash,
        string $totalDebit,
        string $totalCredit,
    ): string {
        // Total records: 2 (file header+control) + 2 per batch (batch header+control) + entries
        $totalRecords = 2 + (2 * $batchCount) + $entryCount;
        $blockCount = (int) ceil($totalRecords / self::BLOCK_SIZE);

        $record =
            '9'
            . str_pad((string) $batchCount, 6, '0', STR_PAD_LEFT)
            . str_pad((string) $blockCount, 6, '0', STR_PAD_LEFT)
            . str_pad((string) $entryCount, 8, '0', STR_PAD_LEFT)
            . str_pad($entryHash, 10, '0', STR_PAD_LEFT)
            . str_pad($totalDebit, 12, '0', STR_PAD_LEFT)
            . str_pad($totalCredit, 12, '0', STR_PAD_LEFT)
            . str_repeat(' ', 39);

        return $this->pad($record);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Ensure the record is exactly 94 characters (right-pad with spaces if short,
     * truncate with an exception if too long to catch generator bugs early).
     */
    private function pad(string $record): string
    {
        $len = strlen($record);

        if ($len > self::RECORD_LENGTH) {
            // Truncate to allowed length — prefer this over silent data loss
            return substr($record, 0, self::RECORD_LENGTH);
        }

        return str_pad($record, self::RECORD_LENGTH, ' ');
    }

    /**
     * Convert a decimal dollar string to integer cents.
     *
     * @param string|int|float|null $amount
     */
    private function toCents(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round((float) $amount * 100);
    }

    /**
     * Format a dollar amount as a 12-digit zero-padded cents string for file control.
     */
    private function formatAmount(string $amount): string
    {
        return str_pad((string) $this->toCents($amount), 12, '0', STR_PAD_LEFT);
    }

    /**
     * Compute the NACHA entry hash: sum of 8-digit routing numbers, keep last 10 digits.
     *
     * @param iterable<AchEntry> $entries
     */
    private function computeEntryHash(iterable $entries): string
    {
        $sum = 0;
        foreach ($entries as $entry) {
            // Use first 8 digits of 9-digit routing number
            $routing = str_pad($entry->routing_number, 9, '0', STR_PAD_LEFT);
            $sum += (int) substr($routing, 0, 8);
        }

        // Keep only the last 10 digits
        $hashStr = (string) $sum;
        if (strlen($hashStr) > 10) {
            $hashStr = substr($hashStr, -10);
        }

        return str_pad($hashStr, 10, '0', STR_PAD_LEFT);
    }
}
