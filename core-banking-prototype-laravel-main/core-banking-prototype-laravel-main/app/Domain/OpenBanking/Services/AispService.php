<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Enums\ConsentPermission;

final class AispService
{
    public function __construct(
        private readonly ConsentEnforcementService $enforcement,
    ) {
    }

    /**
     * Get the list of accounts the TPP is authorised to see for the given user.
     *
     * Returns basic account data unless READ_ACCOUNTS_DETAIL permission is held.
     *
     * @return array<int, array<string, string>>
     */
    public function getAccounts(string $consentId, string $tppId, int $userId): array
    {
        $hasDetail = $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_ACCOUNTS_DETAIL->value,
        );

        $hasBasic = $hasDetail || $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_ACCOUNTS_BASIC->value,
        );

        if (! $hasBasic) {
            return [];
        }

        $this->enforcement->logAccess($consentId, $tppId, 'GET /accounts');

        if ($hasDetail) {
            return [
                [
                    'account_id' => 'acc-001',
                    'iban'       => 'DE89370400440532013000',
                    'bban'       => '370400440532013000',
                    'currency'   => 'EUR',
                    'name'       => 'Current Account',
                    'status'     => 'enabled',
                    'owner_name' => 'Demo User',
                    'bic'        => 'SSKMDEMMXXX',
                ],
            ];
        }

        return [
            [
                'account_id' => 'acc-001',
                'iban'       => 'DE89370400440532013000',
                'currency'   => 'EUR',
                'name'       => 'Current Account',
                'status'     => 'enabled',
            ],
        ];
    }

    /**
     * Get detailed information for a single account.
     *
     * @return array<string, string>|null
     */
    public function getAccountDetail(string $consentId, string $tppId, int $userId, string $accountId): ?array
    {
        $hasAccess = $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_ACCOUNTS_DETAIL->value,
            $accountId,
        );

        if (! $hasAccess) {
            return null;
        }

        $this->enforcement->logAccess($consentId, $tppId, "GET /accounts/{$accountId}");

        return [
            'account_id'        => $accountId,
            'iban'              => 'DE89370400440532013000',
            'bban'              => '370400440532013000',
            'currency'          => 'EUR',
            'name'              => 'Current Account',
            'status'            => 'enabled',
            'owner_name'        => 'Demo User',
            'bic'               => 'SSKMDEMMXXX',
            'usage'             => 'PRIV',
            'cash_account_type' => 'CACC',
        ];
    }

    /**
     * Get balances for a specific account.
     *
     * @return array<string, mixed>|null
     */
    public function getBalances(string $consentId, string $tppId, int $userId, string $accountId): ?array
    {
        $hasAccess = $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_BALANCES->value,
            $accountId,
        );

        if (! $hasAccess) {
            return null;
        }

        $this->enforcement->logAccess($consentId, $tppId, "GET /accounts/{$accountId}/balances");

        return [
            'account_id' => $accountId,
            'balances'   => [
                [
                    'balance_type'   => 'closingBooked',
                    'balance_amount' => [
                        'currency' => 'EUR',
                        'amount'   => '1250.00',
                    ],
                    'reference_date'        => date('Y-m-d'),
                    'credit_limit_included' => false,
                ],
                [
                    'balance_type'   => 'interimAvailable',
                    'balance_amount' => [
                        'currency' => 'EUR',
                        'amount'   => '1100.00',
                    ],
                    'reference_date'        => date('Y-m-d'),
                    'credit_limit_included' => false,
                ],
            ],
        ];
    }

    /**
     * Get transactions for a specific account, optionally filtered by date range.
     *
     * @return array<string, mixed>
     */
    public function getTransactions(
        string $consentId,
        string $tppId,
        int $userId,
        string $accountId,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        $hasDetail = $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_TRANSACTIONS_DETAIL->value,
            $accountId,
        );

        $hasBasic = $hasDetail || $this->enforcement->validateAccess(
            $tppId,
            $userId,
            ConsentPermission::READ_TRANSACTIONS_BASIC->value,
            $accountId,
        );

        if (! $hasBasic) {
            return [];
        }

        $this->enforcement->logAccess($consentId, $tppId, "GET /accounts/{$accountId}/transactions");

        $booked = [
            [
                'transaction_id'     => 'txn-001',
                'booking_date'       => date('Y-m-d', strtotime('-2 days')),
                'value_date'         => date('Y-m-d', strtotime('-2 days')),
                'transaction_amount' => [
                    'currency' => 'EUR',
                    'amount'   => '-45.00',
                ],
                'creditor_name'   => 'ACME Corp',
                'remittance_info' => 'Invoice 12345',
            ],
            [
                'transaction_id'     => 'txn-002',
                'booking_date'       => date('Y-m-d', strtotime('-1 day')),
                'value_date'         => date('Y-m-d', strtotime('-1 day')),
                'transaction_amount' => [
                    'currency' => 'EUR',
                    'amount'   => '2500.00',
                ],
                'debtor_name'     => 'Employer Ltd',
                'remittance_info' => 'Salary March 2026',
            ],
        ];

        // Apply date filter if provided
        if ($fromDate !== null) {
            $from = $fromDate;
            $booked = array_values(array_filter(
                $booked,
                static fn (array $txn): bool => (string) $txn['booking_date'] >= $from,
            ));
        }

        if ($toDate !== null) {
            $to = $toDate;
            $booked = array_values(array_filter(
                $booked,
                static fn (array $txn): bool => (string) $txn['booking_date'] <= $to,
            ));
        }

        return [
            'account_id' => $accountId,
            'booked'     => $booked,
            'pending'    => [],
        ];
    }
}
