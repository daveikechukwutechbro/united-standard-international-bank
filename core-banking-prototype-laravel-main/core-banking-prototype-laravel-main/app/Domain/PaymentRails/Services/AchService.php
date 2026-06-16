<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Services;

use App\Domain\PaymentRails\Enums\AchSecCode;
use App\Domain\PaymentRails\Enums\RailStatus;
use App\Domain\PaymentRails\Models\AchBatch;
use App\Domain\PaymentRails\Models\AchEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Main ACH service — orchestrates batch creation, NACHA file generation,
 * and return-file processing.
 */
final class AchService
{
    public function __construct(
        private readonly NachaFileGenerator $generator,
        private readonly NachaFileParser $parser,
    ) {
    }

    /**
     * Originate a single-entry ACH credit (push funds to receiver).
     *
     * Transaction codes:
     *   22 = checking account credit (deposit)
     *   32 = savings account credit
     *
     * @param  numeric-string $amount  Dollar amount, e.g. "250.00"
     */
    public function originateCredit(
        int $userId,
        string $routingNumber,
        string $accountNumber,
        string $amount,
        string $name,
        string $secCode = 'PPD',
    ): AchBatch {
        /** @var numeric-string $numericAmount */
        $numericAmount = $amount;

        return $this->createBatch(
            userId: $userId,
            secCode: $secCode,
            entries: [
                [
                    'routing_number'   => $routingNumber,
                    'account_number'   => $accountNumber,
                    'amount'           => $numericAmount,
                    'transaction_code' => '22', // Checking account credit
                    'name'             => $name,
                ],
            ],
        );
    }

    /**
     * Originate a single-entry ACH debit (pull funds from receiver).
     *
     * Transaction codes:
     *   27 = checking account debit
     *   37 = savings account debit
     *
     * @param  numeric-string $amount  Dollar amount, e.g. "250.00"
     */
    public function originateDebit(
        int $userId,
        string $routingNumber,
        string $accountNumber,
        string $amount,
        string $name,
        string $secCode = 'PPD',
    ): AchBatch {
        /** @var numeric-string $numericAmount */
        $numericAmount = $amount;

        return $this->createBatch(
            userId: $userId,
            secCode: $secCode,
            entries: [
                [
                    'routing_number'   => $routingNumber,
                    'account_number'   => $accountNumber,
                    'amount'           => $numericAmount,
                    'transaction_code' => '27', // Checking account debit
                    'name'             => $name,
                ],
            ],
        );
    }

    /**
     * Create a multi-entry ACH batch.
     *
     * Each entry array must contain:
     *   - routing_number   string
     *   - account_number   string
     *   - amount           string  (dollar amount, e.g. "100.00")
     *   - transaction_code string  (22/27/32/37)
     *   - name             string
     *
     * Optional per entry:
     *   - individual_id    string
     *
     * @param array<int, array{
     *     routing_number: string,
     *     account_number: string,
     *     amount: numeric-string,
     *     transaction_code: string,
     *     name: string,
     *     individual_id?: string
     * }> $entries
     */
    public function createBatch(
        int $userId,
        string $secCode,
        array $entries,
        bool $sameDay = false,
    ): AchBatch {
        // Validate SEC code
        AchSecCode::from($secCode);

        /** @var numeric-string $totalDebit */
        $totalDebit = '0.00';
        /** @var numeric-string $totalCredit */
        $totalCredit = '0.00';

        foreach ($entries as $entry) {
            $code = $entry['transaction_code'];
            /** @var numeric-string $amount */
            $amount = $entry['amount'];

            // Debit codes: 27 (checking), 37 (savings), 23/33 (prenote debit)
            if (in_array($code, ['27', '37', '23', '33'], true)) {
                /** @var numeric-string $totalDebit */
                $totalDebit = $this->addAmounts($totalDebit, $amount);
            } else {
                // Credit codes: 22 (checking), 32 (savings), etc.
                /** @var numeric-string $totalCredit */
                $totalCredit = $this->addAmounts($totalCredit, $amount);
            }
        }

        return DB::transaction(function () use ($userId, $secCode, $entries, $sameDay, $totalDebit, $totalCredit): AchBatch {
            $batch = AchBatch::create([
                'batch_id'        => Str::uuid()->toString(),
                'user_id'         => $userId,
                'sec_code'        => $secCode,
                'status'          => RailStatus::PENDING,
                'entry_count'     => count($entries),
                'total_debit'     => $totalDebit,
                'total_credit'    => $totalCredit,
                'same_day'        => $sameDay,
                'settlement_date' => null,
            ]);

            $traceSeq = 1;
            $origDfi = str_pad(
                substr((string) config('payment_rails.ach.originating_dfi', ''), 0, 8),
                8,
                '0',
                STR_PAD_LEFT,
            );

            foreach ($entries as $entry) {
                $traceNumber = $origDfi . str_pad((string) $traceSeq++, 7, '0', STR_PAD_LEFT);

                AchEntry::create([
                    'batch_id'         => $batch->id,
                    'trace_number'     => $traceNumber,
                    'routing_number'   => $entry['routing_number'],
                    'account_number'   => $entry['account_number'],
                    'amount'           => $entry['amount'],
                    'transaction_code' => $entry['transaction_code'],
                    'individual_name'  => $entry['name'],
                    'individual_id'    => $entry['individual_id'] ?? null,
                    'status'           => RailStatus::PENDING,
                ]);
            }

            return $batch->fresh() ?? $batch;
        });
    }

    /**
     * Generate a NACHA file for the batch and persist it on the model.
     */
    public function generateFile(AchBatch $batch): string
    {
        $content = $this->generator->generate($batch);

        $batch->update(['file_content' => $content]);

        return $content;
    }

    /**
     * Process an ACH return file: parse, update entry statuses, return structured results.
     *
     * @return array<int, array{
     *     trace_number: string,
     *     return_code: string,
     *     original_entry: array<string, string>
     * }>
     */
    public function processReturns(string $returnFileContent): array
    {
        $returns = $this->parser->parseReturnEntries($returnFileContent);

        foreach ($returns as $return) {
            $traceNumber = $return['trace_number'];
            $returnCode = $return['return_code'];

            /** @var AchEntry|null $entry */
            $entry = AchEntry::where('trace_number', $traceNumber)->first();

            if ($entry === null) {
                continue;
            }

            $entry->update([
                'status'      => RailStatus::RETURNED,
                'return_code' => $returnCode,
            ]);

            // Also mark the parent batch as returned if all entries are returned
            /** @var AchBatch|null $batch */
            $batch = $entry->batch;
            if ($batch !== null) {
                $allReturned = $batch->entries()
                    ->where('status', '!=', RailStatus::RETURNED->value)
                    ->doesntExist();

                if ($allReturned) {
                    $batch->update(['status' => RailStatus::RETURNED]);
                }
            }
        }

        return $returns;
    }

    /**
     * Retrieve a batch by its batch_id UUID.
     */
    public function getBatchStatus(string $batchId): ?AchBatch
    {
        return AchBatch::where('batch_id', $batchId)->first();
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Add two decimal-string amounts together, returning a decimal string.
     *
     * @param  numeric-string $a
     * @param  numeric-string $b
     * @return numeric-string
     */
    private function addAmounts(string $a, string $b): string
    {
        return bcadd($a, $b, 2);
    }
}
