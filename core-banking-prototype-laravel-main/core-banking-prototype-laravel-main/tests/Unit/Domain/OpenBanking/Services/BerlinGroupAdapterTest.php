<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Services\BerlinGroupAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Behavioural tests for BerlinGroupAdapter.
 *
 * Verifies that the adapter produces correctly structured output conforming
 * to the Berlin Group NextGenPSD2 specification.
 */
class BerlinGroupAdapterTest extends TestCase
{
    private BerlinGroupAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new BerlinGroupAdapter();
    }

    #[Test]
    public function format_account_list_wraps_accounts_in_expected_berlin_group_structure(): void
    {
        $accounts = [
            [
                'account_id' => 'acc-001',
                'iban'       => 'DE89370400440532013000',
                'currency'   => 'EUR',
                'name'       => 'Current Account',
                'status'     => 'enabled',
            ],
        ];

        $result = $this->adapter->formatAccountList($accounts);

        $this->assertArrayHasKey('accounts', $result);
        $this->assertIsArray($result['accounts']);
        $this->assertCount(1, $result['accounts']);

        $account = $result['accounts'][0];
        $this->assertEquals('acc-001', $account['resourceId']);
        $this->assertEquals('DE89370400440532013000', $account['iban']);
        $this->assertEquals('EUR', $account['currency']);
        $this->assertEquals('Current Account', $account['name']);
        $this->assertEquals('enabled', $account['status']);
    }

    #[Test]
    public function format_account_list_returns_empty_accounts_array_when_no_accounts(): void
    {
        $result = $this->adapter->formatAccountList([]);

        $this->assertArrayHasKey('accounts', $result);
        $this->assertIsArray($result['accounts']);
        $this->assertCount(0, $result['accounts']);
    }

    #[Test]
    public function format_account_list_maps_optional_fields_when_present(): void
    {
        $accounts = [
            [
                'account_id' => 'acc-002',
                'iban'       => 'DE89370400440532013000',
                'currency'   => 'EUR',
                'name'       => 'Savings Account',
                'status'     => 'enabled',
                'owner_name' => 'Jane Doe',
                'bic'        => 'DEUTDEDB',
                'usage'      => 'PRIV',
            ],
        ];

        $result = $this->adapter->formatAccountList($accounts);
        $account = $result['accounts'][0];

        $this->assertEquals('Jane Doe', $account['ownerName']);
        $this->assertEquals('DEUTDEDB', $account['bic']);
        $this->assertEquals('PRIV', $account['usage']);
    }

    #[Test]
    public function format_error_response_returns_tpp_messages_array(): void
    {
        $result = $this->adapter->formatErrorResponse('CONSENT_INVALID', 'Consent is not authorised');

        $this->assertArrayHasKey('tppMessages', $result);
        $this->assertIsArray($result['tppMessages']);
        $this->assertCount(1, $result['tppMessages']);

        $message = $result['tppMessages'][0];
        $this->assertArrayHasKey('category', $message);
        $this->assertArrayHasKey('code', $message);
        $this->assertArrayHasKey('text', $message);

        $this->assertEquals('ERROR', $message['category']);
        $this->assertEquals('CONSENT_INVALID', $message['code']);
        $this->assertEquals('Consent is not authorised', $message['text']);
    }

    #[Test]
    public function format_balances_returns_balance_amount_structure(): void
    {
        $balances = [
            'account_id' => 'acc-001',
            'balances'   => [
                [
                    'balance_type'   => 'closingBooked',
                    'balance_amount' => [
                        'currency' => 'EUR',
                        'amount'   => '1250.00',
                    ],
                    'reference_date'        => '2026-03-29',
                    'credit_limit_included' => false,
                ],
            ],
        ];

        $result = $this->adapter->formatBalances($balances);

        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('balances', $result);
        $this->assertEquals(['resourceId' => 'acc-001'], $result['account']);
        $this->assertCount(1, $result['balances']);

        $balance = $result['balances'][0];
        $this->assertArrayHasKey('balanceAmount', $balance);
        $this->assertArrayHasKey('balanceType', $balance);
        $this->assertEquals('closingBooked', $balance['balanceType']);
        $this->assertEquals('EUR', $balance['balanceAmount']['currency']);
        $this->assertEquals('1250.00', $balance['balanceAmount']['amount']);
    }

    #[Test]
    public function format_transactions_splits_into_booked_and_pending(): void
    {
        $transactions = [
            'account_id' => 'acc-001',
            'booked'     => [
                [
                    'transaction_id'     => 'txn-001',
                    'booking_date'       => '2026-03-27',
                    'value_date'         => '2026-03-27',
                    'transaction_amount' => ['currency' => 'EUR', 'amount' => '-45.00'],
                    'creditor_name'      => 'ACME Corp',
                    'remittance_info'    => 'Invoice 12345',
                ],
            ],
            'pending' => [],
        ];

        $result = $this->adapter->formatTransactions($transactions);

        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('booked', $result['transactions']);
        $this->assertArrayHasKey('pending', $result['transactions']);

        $booked = $result['transactions']['booked'];
        $this->assertCount(1, $booked);

        $txn = $booked[0];
        $this->assertEquals('txn-001', $txn['transactionId']);
        $this->assertEquals('2026-03-27', $txn['bookingDate']);
        $this->assertArrayHasKey('transactionAmount', $txn);
        $this->assertEquals('EUR', $txn['transactionAmount']['currency']);
        $this->assertEquals('-45.00', $txn['transactionAmount']['amount']);
        $this->assertEquals('ACME Corp', $txn['creditorName']);
    }

    #[Test]
    public function format_consent_response_uses_consent_id_and_links(): void
    {
        $consent = [
            'id'     => 'cns-abc-123',
            'status' => 'valid',
        ];

        $result = $this->adapter->formatConsentResponse($consent);

        $this->assertArrayHasKey('consentId', $result);
        $this->assertArrayHasKey('consentStatus', $result);
        $this->assertArrayHasKey('_links', $result);

        $this->assertEquals('cns-abc-123', $result['consentId']);
        $this->assertEquals('valid', $result['consentStatus']);
        $this->assertArrayHasKey('self', $result['_links']);
        $this->assertArrayHasKey('status', $result['_links']);
    }

    #[Test]
    public function format_payment_response_uses_transaction_status_and_payment_id(): void
    {
        $payment = [
            'payment_id'  => 'pay-xyz-456',
            'status'      => 'AcceptedSettlementInProcess',
            'status_code' => 'ACSP',
        ];

        $result = $this->adapter->formatPaymentResponse($payment);

        $this->assertArrayHasKey('transactionStatus', $result);
        $this->assertArrayHasKey('paymentId', $result);
        $this->assertArrayHasKey('_links', $result);

        $this->assertEquals('ACSP', $result['transactionStatus']);
        $this->assertEquals('pay-xyz-456', $result['paymentId']);
    }
}
