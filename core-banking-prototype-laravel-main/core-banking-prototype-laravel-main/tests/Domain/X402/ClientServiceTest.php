<?php

declare(strict_types=1);

namespace Tests\Domain\X402;

use App\Domain\X402\Contracts\X402SignerInterface;
use App\Domain\X402\DataObjects\PaymentRequired;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\ResourceInfo;
use App\Domain\X402\Exceptions\X402InvalidPayloadException;
use App\Domain\X402\Models\X402SpendingLimit;
use App\Domain\X402\Services\X402ClientService;
use App\Domain\X402\Services\X402HeaderCodecService;
use RuntimeException;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    private X402ClientService $service;

    private X402HeaderCodecService $codec;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'x402.client.signer_address'                        => '0xClientSigner',
            'x402.client.max_auto_pay_amount'                   => '100000',
            'x402.agent_spending.default_per_transaction_limit' => '50000',
            'x402.agent_spending.require_approval_above'        => '1000000',
        ]);

        $signer = $this->createMock(X402SignerInterface::class);
        $signer->method('signTransferAuthorization')
            ->willReturn([
                'signature'     => '0xmocksig',
                'authorization' => [
                    'from'        => '0xClientSigner',
                    'to'          => '0xRecipient',
                    'value'       => '10000',
                    'validAfter'  => '0',
                    'validBefore' => (string) (time() + 60),
                    'nonce'       => '0xnonce',
                ],
            ]);
        $signer->method('getAddress')->willReturn('0xClientSigner');

        $this->codec = new X402HeaderCodecService();

        $this->service = new X402ClientService($signer, $this->codec);
    }

    private function buildPaymentRequiredHeader(string $amount = '10000'): string
    {
        $pr = new PaymentRequired(
            x402Version: 2,
            resource: new ResourceInfo(
                url: '/api/v1/premium/data',
                description: 'Premium data',
                mimeType: 'application/json',
            ),
            accepts: [
                new PaymentRequirements(
                    scheme: 'exact',
                    network: 'eip155:8453',
                    asset: '0xUSDC',
                    amount: $amount,
                    payTo: '0xRecipient',
                    maxTimeoutSeconds: 60,
                ),
            ],
        );

        return $pr->toBase64();
    }

    public function test_spending_limit_daily_enforcement(): void
    {
        X402SpendingLimit::create([
            'agent_id'              => 'test-agent',
            'agent_type'            => 'ai_agent',
            'daily_limit'           => '5000',
            'spent_today'           => '4500',
            'per_transaction_limit' => '50000',
            'auto_pay_enabled'      => true,
            'limit_resets_at'       => now()->addDay(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('would exceed the daily limit');

        $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('10000'),
            'test-agent',
        );
    }

    public function test_spending_limit_per_transaction_enforcement(): void
    {
        X402SpendingLimit::create([
            'agent_id'              => 'test-agent',
            'agent_type'            => 'ai_agent',
            'daily_limit'           => '10000000',
            'spent_today'           => '0',
            'per_transaction_limit' => '5000',
            'auto_pay_enabled'      => true,
            'limit_resets_at'       => now()->addDay(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeds per-transaction limit');

        $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('10000'),
            'test-agent',
        );
    }

    public function test_global_cap_enforcement_when_no_limit_configured(): void
    {
        // No spending limit for this agent — uses global config
        config(['x402.client.max_auto_pay_amount' => '5000']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeds the auto-pay limit');

        $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('10000'),
            'unconfigured-agent',
        );
    }

    public function test_auto_pay_threshold_enforcement(): void
    {
        X402SpendingLimit::create([
            'agent_id'              => 'test-agent',
            'agent_type'            => 'ai_agent',
            'daily_limit'           => '10000000',
            'spent_today'           => '0',
            'per_transaction_limit' => '10000000',
            'auto_pay_enabled'      => false,
            'limit_resets_at'       => now()->addDay(),
        ]);

        config(['x402.agent_spending.require_approval_above' => '5000']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Manual approval is required');

        $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('10000'),
            'test-agent',
        );
    }

    public function test_non_positive_amount_rejected(): void
    {
        $this->expectException(X402InvalidPayloadException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('0'),
            'test-agent',
        );
    }

    public function test_successful_payment_returns_signature_header(): void
    {
        X402SpendingLimit::create([
            'agent_id'              => 'test-agent',
            'agent_type'            => 'ai_agent',
            'daily_limit'           => '10000000',
            'spent_today'           => '0',
            'per_transaction_limit' => '10000000',
            'auto_pay_enabled'      => true,
            'limit_resets_at'       => now()->addDay(),
        ]);

        $result = $this->service->handlePaymentRequired(
            $this->buildPaymentRequiredHeader('10000'),
            'test-agent',
        );

        $this->assertArrayHasKey('PAYMENT-SIGNATURE', $result);
        $this->assertNotEmpty($result['PAYMENT-SIGNATURE']);
    }
}
