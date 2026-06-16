<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services\Drivers;

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class TigerBeetleDriver implements LedgerDriverInterface
{
    private string $baseUrl;

    private int $clusterId;

    private int $timeoutMs;

    public function __construct()
    {
        $address = (string) config('ledger.tigerbeetle.addresses', '127.0.0.1:3001');
        $this->baseUrl = "http://{$address}";
        $this->clusterId = (int) config('ledger.tigerbeetle.cluster_id', 0);
        $this->timeoutMs = (int) config('ledger.tigerbeetle.timeout_ms', 5000);
    }

    public function post(JournalEntry $entry): void
    {
        // Separate lines into debit and credit sides
        $debitLines = [];
        $creditLines = [];

        foreach ($entry->lines as $line) {
            if ((float) $line->debit_amount > 0) {
                $debitLines[] = $line;
            }

            if ((float) $line->credit_amount > 0) {
                $creditLines[] = $line;
            }
        }

        $tbTransfers = [];

        foreach ($debitLines as $i => $debit) {
            $credit = $creditLines[$i] ?? $creditLines[0];
            $amount = (int) round((float) $debit->debit_amount * 10000);

            $tbTransfers[] = [
                'id'                => $this->generateId(),
                'debit_account_id'  => $this->accountCodeToId($debit->account_code),
                'credit_account_id' => $this->accountCodeToId($credit->account_code),
                'amount'            => $amount,
                'ledger'            => $this->clusterId,
                'code'              => crc32($entry->source_domain ?? 'manual'),
                'user_data_128'     => $this->uuidToInt128($entry->id),
                'flags'             => 0,
            ];
        }

        try {
            $response = Http::timeout((int) ($this->timeoutMs / 1000))
                ->post("{$this->baseUrl}/create_transfers", $tbTransfers);

            if (! $response->successful()) {
                throw new RuntimeException("TigerBeetle create_transfers failed: {$response->status()}");
            }

            Log::info('TigerBeetle transfers posted', [
                'entry_id'       => $entry->id,
                'transfer_count' => count($tbTransfers),
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException("TigerBeetle connection failed: {$e->getMessage()}");
        }
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function balance(string $accountCode, ?Carbon $asOf = null): array
    {
        $accountId = $this->accountCodeToId($accountCode);

        try {
            $response = Http::timeout((int) ($this->timeoutMs / 1000))
                ->post("{$this->baseUrl}/lookup_accounts", [$accountId]);

            if ($response->successful() && ! empty($response->json())) {
                $account = $response->json()[0] ?? [];
                $debits = (int) ($account['debits_posted'] ?? 0);
                $credits = (int) ($account['credits_posted'] ?? 0);
                $balance = ($debits - $credits) / 10000;

                return [
                    'amount'   => number_format(abs($balance), 4, '.', ''),
                    'currency' => (string) config('ledger.default_currency', 'USD'),
                ];
            }
        } catch (ConnectionException) {
            Log::warning('TigerBeetle lookup_accounts failed, falling back to zero balance', [
                'account_code' => $accountCode,
            ]);
        }

        return ['amount' => '0.0000', 'currency' => (string) config('ledger.default_currency', 'USD')];
    }

    /**
     * TigerBeetle doesn't support point-in-time queries natively.
     * Returns empty — the EloquentDriver handles this for reporting.
     *
     * @return array<string, array{debit: string, credit: string, balance: string}>
     */
    public function trialBalance(?Carbon $asOf = null): array
    {
        Log::info('TigerBeetle trialBalance called — delegating to EloquentDriver for reporting');

        return [];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function accountHistory(string $accountCode, Carbon $from, Carbon $to): Collection
    {
        $accountId = $this->accountCodeToId($accountCode);

        try {
            $response = Http::timeout((int) ($this->timeoutMs / 1000))
                ->post("{$this->baseUrl}/get_account_transfers", [
                    'account_id'    => $accountId,
                    'timestamp_min' => $from->getTimestampMs(),
                    'timestamp_max' => $to->getTimestampMs(),
                    'limit'         => 1000,
                ]);

            if ($response->successful()) {
                return collect($response->json() ?? []);
            }
        } catch (ConnectionException) {
            Log::warning('TigerBeetle get_account_transfers failed', ['account_code' => $accountCode]);
        }

        return collect();
    }

    /**
     * Deterministic mapping: account code → TigerBeetle u128 ID.
     * Uses CRC32 for simplicity (production would use a persistent mapping table).
     */
    private function accountCodeToId(string $code): int
    {
        return abs(crc32($code));
    }

    /**
     * Generate a unique monotonic transfer ID using high-resolution time.
     */
    private function generateId(): int
    {
        return abs((int) hrtime(true));
    }

    /**
     * Convert a UUID string to a 63-bit integer for TigerBeetle user_data_128.
     */
    private function uuidToInt128(string $uuid): int
    {
        $hex = str_replace('-', '', $uuid);

        return abs((int) hexdec(substr($hex, 0, 15)));
    }
}
