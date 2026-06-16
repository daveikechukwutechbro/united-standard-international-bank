<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

final class BerlinGroupAdapter
{
    /**
     * Format a list of accounts per Berlin Group NextGenPSD2 specification.
     *
     * @param array<int, array<string, mixed>> $accounts
     * @return array{accounts: array<int, array<string, mixed>>}
     */
    public function formatAccountList(array $accounts): array
    {
        return [
            'accounts' => array_map(
                fn (array $account): array => $this->mapAccount($account),
                $accounts,
            ),
        ];
    }

    /**
     * Format a single account detail per Berlin Group spec.
     *
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    public function formatAccountDetail(array $account): array
    {
        return $this->mapAccount($account);
    }

    /**
     * Format account balances per Berlin Group NextGenPSD2 specification.
     * Uses `balanceAmount` with nested `currency` and `amount` fields.
     *
     * @param array<string, mixed> $balances
     * @return array{account: array<string, string>, balances: array<int, array<string, mixed>>}
     */
    public function formatBalances(array $balances): array
    {
        /** @var array<int, array<string, mixed>> $rawBalances */
        $rawBalances = $balances['balances'] ?? [];

        $formatted = array_map(static function (array $balance): array {
            /** @var array{currency?: string, amount?: string} $rawAmount */
            $rawAmount = $balance['balance_amount'] ?? [];

            return [
                'balanceType'   => $balance['balance_type'] ?? 'unknown',
                'balanceAmount' => [
                    'currency' => $rawAmount['currency'] ?? 'EUR',
                    'amount'   => $rawAmount['amount'] ?? '0.00',
                ],
                'referenceDate'       => $balance['reference_date'] ?? date('Y-m-d'),
                'creditLimitIncluded' => $balance['credit_limit_included'] ?? false,
            ];
        }, $rawBalances);

        return [
            'account'  => ['resourceId' => (string) ($balances['account_id'] ?? '')],
            'balances' => $formatted,
        ];
    }

    /**
     * Format transaction list per Berlin Group NextGenPSD2 specification.
     * Transactions are split into `booked` and `pending` arrays.
     *
     * @param array<string, mixed> $transactions
     * @return array{account: array<string, string>, transactions: array{booked: array<int, array<string, mixed>>, pending: array<int, array<string, mixed>>}}
     */
    public function formatTransactions(array $transactions): array
    {
        /** @var array<int, array<string, mixed>> $booked */
        $booked = $transactions['booked'] ?? [];

        /** @var array<int, array<string, mixed>> $pending */
        $pending = $transactions['pending'] ?? [];

        $mapTransaction = static function (array $txn): array {
            /** @var array{currency?: string, amount?: string} $rawAmount */
            $rawAmount = $txn['transaction_amount'] ?? [];

            $mapped = [
                'transactionId'     => $txn['transaction_id'] ?? '',
                'bookingDate'       => $txn['booking_date'] ?? '',
                'valueDate'         => $txn['value_date'] ?? '',
                'transactionAmount' => [
                    'currency' => $rawAmount['currency'] ?? 'EUR',
                    'amount'   => $rawAmount['amount'] ?? '0.00',
                ],
                'remittanceInformationUnstructured' => $txn['remittance_info'] ?? '',
            ];

            if (isset($txn['creditor_name'])) {
                $mapped['creditorName'] = (string) $txn['creditor_name'];
            }

            if (isset($txn['debtor_name'])) {
                $mapped['debtorName'] = (string) $txn['debtor_name'];
            }

            return $mapped;
        };

        return [
            'account'      => ['resourceId' => (string) ($transactions['account_id'] ?? '')],
            'transactions' => [
                'booked'  => array_map($mapTransaction, $booked),
                'pending' => array_map($mapTransaction, $pending),
            ],
        ];
    }

    /**
     * Format a consent response per Berlin Group NextGenPSD2 specification.
     *
     * @param array<string, mixed> $consent
     * @return array{consentId: string, consentStatus: string, _links: array<string, array<string, string>>}
     */
    public function formatConsentResponse(array $consent): array
    {
        $consentId = (string) ($consent['id'] ?? $consent['consent_id'] ?? '');
        $status = (string) ($consent['status'] ?? 'received');

        return [
            'consentId'     => $consentId,
            'consentStatus' => $status,
            '_links'        => [
                'self' => [
                    'href' => "/v1/consents/{$consentId}",
                ],
                'status' => [
                    'href' => "/v1/consents/{$consentId}/status",
                ],
            ],
        ];
    }

    /**
     * Format a payment initiation response per Berlin Group NextGenPSD2 specification.
     *
     * @param array<string, mixed> $payment
     * @return array{transactionStatus: string, paymentId: string, _links: array<string, array<string, string>>}
     */
    public function formatPaymentResponse(array $payment): array
    {
        $paymentId = (string) ($payment['payment_id'] ?? '');
        $status = (string) ($payment['status_code'] ?? $payment['status'] ?? 'RCVD');

        return [
            'transactionStatus' => $status,
            'paymentId'         => $paymentId,
            '_links'            => [
                'self' => [
                    'href' => "/v1/payments/credit-transfers/{$paymentId}",
                ],
                'status' => [
                    'href' => "/v1/payments/credit-transfers/{$paymentId}/status",
                ],
            ],
        ];
    }

    /**
     * Format an error response per Berlin Group NextGenPSD2 specification.
     * Uses the `tppMessages` array structure.
     *
     * @return array{tppMessages: array<int, array{category: string, code: string, text: string}>}
     */
    public function formatErrorResponse(string $code, string $message): array
    {
        return [
            'tppMessages' => [
                [
                    'category' => 'ERROR',
                    'code'     => $code,
                    'text'     => $message,
                ],
            ],
        ];
    }

    /**
     * Map internal account structure to Berlin Group account fields.
     *
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    private function mapAccount(array $account): array
    {
        $mapped = [
            'resourceId' => (string) ($account['account_id'] ?? ''),
            'iban'       => (string) ($account['iban'] ?? ''),
            'currency'   => (string) ($account['currency'] ?? 'EUR'),
            'name'       => (string) ($account['name'] ?? ''),
            'status'     => (string) ($account['status'] ?? 'enabled'),
        ];

        if (isset($account['bban'])) {
            $mapped['bban'] = (string) $account['bban'];
        }

        if (isset($account['owner_name'])) {
            $mapped['ownerName'] = (string) $account['owner_name'];
        }

        if (isset($account['bic'])) {
            $mapped['bic'] = (string) $account['bic'];
        }

        if (isset($account['usage'])) {
            $mapped['usage'] = (string) $account['usage'];
        }

        if (isset($account['cash_account_type'])) {
            $mapped['cashAccountType'] = (string) $account['cash_account_type'];
        }

        return $mapped;
    }
}
