<?php

declare(strict_types=1);

namespace Tests\Domain\X402;

use App\Domain\X402\Contracts\FacilitatorClientInterface;
use App\Domain\X402\DataObjects\MonetizedRouteConfig;
use App\Domain\X402\DataObjects\PaymentPayload;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\ResourceInfo;
use App\Domain\X402\DataObjects\SettleResponse;
use App\Domain\X402\Events\X402PaymentFailed;
use App\Domain\X402\Events\X402PaymentSettled;
use App\Domain\X402\Events\X402PaymentVerified;
use App\Domain\X402\Exceptions\X402SettlementException;
use App\Domain\X402\Models\X402Payment;
use App\Domain\X402\Services\X402PaymentVerificationService;
use App\Domain\X402\Services\X402PricingService;
use App\Domain\X402\Services\X402SettlementService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    /** @var FacilitatorClientInterface&MockInterface */
    private FacilitatorClientInterface $facilitator;

    private X402SettlementService $service;

    private MonetizedRouteConfig $config;

    private PaymentPayload $payload;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config([
            'x402.enabled'                    => true,
            'x402.server.pay_to'              => '0xRecipient',
            'x402.server.max_timeout_seconds' => 60,
            'x402.assets.eip155:8453.USDC'    => '0xUSDC',
        ]);

        /** @var FacilitatorClientInterface&MockInterface $facilitator */
        $facilitator = Mockery::mock(FacilitatorClientInterface::class);
        $this->facilitator = $facilitator;
        $pricingService = new X402PricingService();
        $verificationService = new X402PaymentVerificationService($this->facilitator, $pricingService);

        $this->service = new X402SettlementService($this->facilitator, $verificationService);

        $this->config = new MonetizedRouteConfig(
            method: 'GET',
            path: '/api/v1/premium/data',
            price: '0.01',
            network: 'eip155:8453',
        );

        $requirements = new PaymentRequirements(
            scheme: 'exact',
            network: 'eip155:8453',
            asset: '0xUSDC',
            amount: '10000',
            payTo: '0xRecipient',
            maxTimeoutSeconds: 60,
        );

        $this->payload = new PaymentPayload(
            x402Version: 2,
            resource: new ResourceInfo(
                url: '/api/v1/premium/data',
                description: 'Premium data',
                mimeType: 'application/json',
            ),
            accepted: $requirements,
            payload: [
                'signature'     => '0xabc123',
                'authorization' => [
                    'from'        => '0xPayerAddress',
                    'to'          => '0xRecipient',
                    'value'       => '10000',
                    'validAfter'  => '0',
                    'validBefore' => (string) (time() + 60),
                    'nonce'       => '0x' . bin2hex(random_bytes(32)),
                ],
            ],
        );
    }

    public function test_successful_settle_dispatches_settled_event(): void
    {
        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andReturn(new SettleResponse(
                success: true,
                payer: '0xPayerAddress',
                transaction: '0xTxHash123',
                network: 'eip155:8453',
            ));

        $result = $this->service->settle($this->payload, $this->config);

        $this->assertTrue($result->success);
        $this->assertSame('0xTxHash123', $result->transaction);

        Event::assertDispatched(X402PaymentVerified::class);
        Event::assertDispatched(X402PaymentSettled::class, function (X402PaymentSettled $event) {
            return $event->transactionHash === '0xTxHash123'
                && $event->network === 'eip155:8453';
        });
    }

    public function test_failed_settle_dispatches_failed_event(): void
    {
        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andReturn(new SettleResponse(
                success: false,
                errorReason: 'insufficient_funds',
                errorMessage: 'Not enough USDC',
            ));

        $result = $this->service->settle($this->payload, $this->config);

        $this->assertFalse($result->success);

        Event::assertDispatched(X402PaymentFailed::class, function (X402PaymentFailed $event) {
            return $event->errorReason === 'insufficient_funds';
        });
    }

    public function test_exception_during_settlement_dispatches_failed_event_and_rethrows(): void
    {
        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andThrow(new X402SettlementException(
                message: 'Network error',
                errorReason: 'network_error',
                errorMessage: 'Network error',
            ));

        $this->expectException(X402SettlementException::class);

        try {
            $this->service->settle($this->payload, $this->config);
        } catch (X402SettlementException $e) {
            Event::assertDispatched(X402PaymentFailed::class, function (X402PaymentFailed $event) {
                return $event->errorReason === 'settlement_exception';
            });

            throw $e;
        }
    }

    public function test_idempotency_duplicate_payload_returns_existing_record(): void
    {
        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andReturn(new SettleResponse(
                success: true,
                payer: '0xPayerAddress',
                transaction: '0xTxHash123',
                network: 'eip155:8453',
            ));

        // First call
        $result1 = $this->service->settle($this->payload, $this->config);
        $this->assertTrue($result1->success);

        // Second call with the same payload should be idempotent (facilitator NOT called again)
        $result2 = $this->service->settle($this->payload, $this->config);
        $this->assertTrue($result2->success);

        // Only one payment record should exist
        $this->assertSame(1, X402Payment::count());
    }

    public function test_payer_address_extracted_from_eip3009_authorization(): void
    {
        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andReturn(new SettleResponse(
                success: true,
                transaction: '0xTxHash',
                network: 'eip155:8453',
            ));

        $this->service->settle($this->payload, $this->config);

        /** @var X402Payment $payment */
        $payment = X402Payment::firstOrFail();
        $this->assertSame('0xPayerAddress', $payment->payer_address);
    }

    public function test_payer_address_extracted_from_permit2_authorization(): void
    {
        $requirements = new PaymentRequirements(
            scheme: 'exact',
            network: 'eip155:8453',
            asset: '0xUSDC',
            amount: '10000',
            payTo: '0xRecipient',
            maxTimeoutSeconds: 60,
        );

        $permit2Payload = new PaymentPayload(
            x402Version: 2,
            resource: new ResourceInfo(
                url: '/api/v1/premium/data',
                description: 'Premium data',
                mimeType: 'application/json',
            ),
            accepted: $requirements,
            payload: [
                'signature'            => '0xdef456',
                'permit2Authorization' => [
                    'from'  => '0xPermit2Payer',
                    'to'    => '0xRecipient',
                    'value' => '10000',
                ],
            ],
        );

        $this->facilitator
            ->shouldReceive('settle')
            ->once()
            ->andReturn(new SettleResponse(
                success: true,
                transaction: '0xTxHash',
                network: 'eip155:8453',
            ));

        $this->service->settle($permit2Payload, $this->config);

        /** @var X402Payment $payment */
        $payment = X402Payment::firstOrFail();
        $this->assertSame('0xPermit2Payer', $payment->payer_address);
    }
}
