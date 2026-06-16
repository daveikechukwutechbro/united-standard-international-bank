<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

final class UkObAdapter
{
    /**
     * Format a list of accounts per UK Open Banking specification.
     * Wraps accounts in a `Data.Account` array with OB-standard metadata.
     *
     * @param array<int, array<string, mixed>> $accounts
     * @return array{Data: array{Account: array<int, array<string, mixed>>}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatAccountList(array $accounts): array
    {
        $mapped = array_map(
            fn (array $account): array => $this->mapAccount($account),
            $accounts,
        );

        return [
            'Data'  => ['Account' => $mapped],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => '/open-banking/v3.1/aisp/accounts'],
        ];
    }

    /**
     * Format a single account detail per UK Open Banking specification.
     *
     * @param array<string, mixed> $account
     * @return array{Data: array{Account: array<int, array<string, mixed>>}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatAccountDetail(array $account): array
    {
        $accountId = (string) ($account['account_id'] ?? '');

        return [
            'Data'  => ['Account' => [$this->mapAccount($account)]],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => "/open-banking/v3.1/aisp/accounts/{$accountId}"],
        ];
    }

    /**
     * Format account balances per UK Open Banking specification.
     * Uses `Amount` with nested `Amount` and `Currency` fields (capitalised).
     *
     * @param array<string, mixed> $balances
     * @return array{Data: array{Balance: array<int, array<string, mixed>>}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatBalances(array $balances): array
    {
        $accountId = (string) ($balances['account_id'] ?? '');

        /** @var array<int, array<string, mixed>> $rawBalances */
        $rawBalances = $balances['balances'] ?? [];

        $formatted = array_map(static function (array $balance) use ($accountId): array {
            /** @var array{currency?: string, amount?: string} $rawAmount */
            $rawAmount = $balance['balance_amount'] ?? [];

            return [
                'AccountId' => $accountId,
                'Type'      => $balance['balance_type'] ?? 'InterimAvailable',
                'Amount'    => [
                    'Amount'   => $rawAmount['amount'] ?? '0.00',
                    'Currency' => $rawAmount['currency'] ?? 'GBP',
                ],
                'CreditDebitIndicator' => 'Credit',
                'DateTime'             => date('c'),
            ];
        }, $rawBalances);

        return [
            'Data'  => ['Balance' => $formatted],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => "/open-banking/v3.1/aisp/accounts/{$accountId}/balances"],
        ];
    }

    /**
     * Format transactions per UK Open Banking specification.
     * Uses the `Data.Transaction` array structure.
     *
     * @param array<string, mixed> $transactions
     * @return array{Data: array{Transaction: array<int, array<string, mixed>>}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatTransactions(array $transactions): array
    {
        $accountId = (string) ($transactions['account_id'] ?? '');

        /** @var array<int, array<string, mixed>> $booked */
        $booked = $transactions['booked'] ?? [];

        /** @var array<int, array<string, mixed>> $pending */
        $pending = $transactions['pending'] ?? [];

        $mapTransaction = static function (array $txn, string $status) use ($accountId): array {
            /** @var array{currency?: string, amount?: string} $rawAmount */
            $rawAmount = $txn['transaction_amount'] ?? [];

            $mapped = [
                'AccountId'       => $accountId,
                'TransactionId'   => $txn['transaction_id'] ?? '',
                'Status'          => $status,
                'BookingDateTime' => isset($txn['booking_date'])
                    ? $txn['booking_date'] . 'T00:00:00Z'
                    : date('c'),
                'ValueDateTime' => isset($txn['value_date'])
                    ? $txn['value_date'] . 'T00:00:00Z'
                    : date('c'),
                'Amount' => [
                    'Amount'   => $rawAmount['amount'] ?? '0.00',
                    'Currency' => $rawAmount['currency'] ?? 'GBP',
                ],
                'TransactionInformation' => $txn['remittance_info'] ?? '',
            ];

            if (isset($txn['creditor_name'])) {
                $mapped['CreditorAgent'] = ['Name' => (string) $txn['creditor_name']];
            }

            if (isset($txn['debtor_name'])) {
                $mapped['DebtorAgent'] = ['Name' => (string) $txn['debtor_name']];
            }

            return $mapped;
        };

        $allTransactions = array_merge(
            array_map(static fn (array $t): array => $mapTransaction($t, 'Booked'), $booked),
            array_map(static fn (array $t): array => $mapTransaction($t, 'Pending'), $pending),
        );

        return [
            'Data'  => ['Transaction' => $allTransactions],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => "/open-banking/v3.1/aisp/accounts/{$accountId}/transactions"],
        ];
    }

    /**
     * Format a consent response per UK Open Banking specification.
     * Uses `Data.ConsentId` and `Data.Status`.
     *
     * @param array<string, mixed> $consent
     * @return array{Data: array{ConsentId: string, Status: string, CreationDateTime: string, Permissions: array<int, string>}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatConsentResponse(array $consent): array
    {
        $consentId = (string) ($consent['id'] ?? $consent['consent_id'] ?? '');
        $status = (string) ($consent['status'] ?? 'AwaitingAuthorisation');

        /** @var array<int, string> $permissions */
        $permissions = $consent['permissions'] ?? [];

        return [
            'Data' => [
                'ConsentId'        => $consentId,
                'Status'           => $status,
                'CreationDateTime' => date('c'),
                'Permissions'      => $permissions,
            ],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => "/open-banking/v3.1/aisp/account-access-consents/{$consentId}"],
        ];
    }

    /**
     * Format a payment initiation response per UK Open Banking specification.
     *
     * @param array<string, mixed> $payment
     * @return array{Data: array{DomesticPaymentId: string, Status: string, CreationDateTime: string}, Meta: array{TotalPages: int}, Links: array{Self: string}}
     */
    public function formatPaymentResponse(array $payment): array
    {
        $paymentId = (string) ($payment['payment_id'] ?? '');
        $status = (string) ($payment['status'] ?? 'AcceptedSettlementInProcess');

        return [
            'Data' => [
                'DomesticPaymentId' => $paymentId,
                'Status'            => $status,
                'CreationDateTime'  => date('c'),
            ],
            'Meta'  => ['TotalPages' => 1],
            'Links' => ['Self' => "/open-banking/v3.1/pisp/domestic-payments/{$paymentId}"],
        ];
    }

    /**
     * Format an error response per UK Open Banking specification.
     * Uses `Code`, `Message`, and `Errors` array structure.
     *
     * @return array{Code: string, Message: string, Errors: array<int, array{ErrorCode: string, Message: string}>}
     */
    public function formatErrorResponse(string $code, string $message): array
    {
        return [
            'Code'    => $code,
            'Message' => $message,
            'Errors'  => [
                [
                    'ErrorCode' => $code,
                    'Message'   => $message,
                ],
            ],
        ];
    }

    /**
     * Map internal account structure to UK Open Banking account fields.
     *
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    private function mapAccount(array $account): array
    {
        $mapped = [
            'AccountId' => (string) ($account['account_id'] ?? ''),
            'Currency'  => (string) ($account['currency'] ?? 'GBP'),
            'Nickname'  => (string) ($account['name'] ?? ''),
            'Status'    => ucfirst((string) ($account['status'] ?? 'Enabled')),
            'Account'   => [
                [
                    'SchemeName'     => 'UK.OBIE.IBAN',
                    'Identification' => (string) ($account['iban'] ?? ''),
                    'Name'           => (string) ($account['owner_name'] ?? $account['name'] ?? ''),
                ],
            ],
        ];

        if (isset($account['bic'])) {
            $mapped['Servicer'] = [
                'SchemeName'     => 'UK.OBIE.BICFI',
                'Identification' => (string) $account['bic'],
            ];
        }

        return $mapped;
    }
}
