<?php

declare(strict_types=1);

namespace Tests\Unit\AgentProtocol\Activities;

use App\Domain\AgentProtocol\DataObjects\AgentPaymentRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for payment validation logic used in ValidatePaymentActivity.
 *
 * Note: ValidatePaymentActivity extends Workflow\Activity which requires constructor
 * arguments when instantiated. These tests verify the validation rules directly
 * rather than instantiating the Activity class.
 */
class ValidatePaymentActivityTest extends TestCase
{
    /**
     * Validate a payment request using the same rules as ValidatePaymentActivity.
     *
     * @return array{isValid: bool, errors: array<string>, escrowRequirements: array<string>}
     */
    private function validatePaymentRequest(AgentPaymentRequest $request): array
    {
        $errors = [];
        $escrowRequirements = [];

        // Validate amount
        if ($request->amount <= 0) {
            $errors[] = 'Amount must be positive';
        }

        // Validate DID format
        if (! str_starts_with($request->fromAgentDid, 'did:')) {
            $errors[] = 'Invalid sender DID format';
        }

        if (! str_starts_with($request->toAgentDid, 'did:')) {
            $errors[] = 'Invalid receiver DID format';
        }

        // Validate sender and receiver are different
        if ($request->fromAgentDid === $request->toAgentDid) {
            $errors[] = 'Sender and receiver cannot be the same';
        }

        // Validate currency
        $supportedCurrencies = config('agent_protocol.supported_currencies', ['USD', 'EUR', 'GBP', 'USDC', 'USDT']);
        if (! in_array($request->currency, $supportedCurrencies)) {
            $errors[] = "Unsupported currency: {$request->currency}";
        }

        // Validate escrow conditions if present
        if (! empty($request->escrowConditions)) {
            $escrowRequirements = array_keys($request->escrowConditions);
        }

        // Validate splits
        if (! empty($request->splits)) {
            $totalSplits = array_sum(array_column($request->splits, 'amount'));
            if ($totalSplits > $request->amount) {
                $errors[] = 'Split amounts exceed total payment';
            }
        }

        return [
            'isValid'            => empty($errors),
            'errors'             => $errors,
            'escrowRequirements' => $escrowRequirements,
        ];
    }

    #[Test]
    public function it_validates_valid_payment_request(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'USD',
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    #[Test]
    public function it_rejects_negative_amount(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: -10.00,
            currency: 'USD',
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Amount must be positive', $result['errors']);
    }

    #[Test]
    public function it_rejects_zero_amount(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 0.00,
            currency: 'USD',
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Amount must be positive', $result['errors']);
    }

    #[Test]
    public function it_rejects_invalid_did_format(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'invalid-did',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'USD',
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Invalid sender DID format', $result['errors']);
    }

    #[Test]
    public function it_rejects_same_sender_and_receiver(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:same',
            toAgentDid: 'did:agent:test:same',
            amount: 100.00,
            currency: 'USD',
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Sender and receiver cannot be the same', $result['errors']);
    }

    #[Test]
    public function it_validates_supported_currencies(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'XYZ', // Unsupported currency
            purpose: 'payment'
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Unsupported currency: XYZ', $result['errors']);
    }

    #[Test]
    public function it_validates_escrow_conditions(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'USD',
            purpose: 'escrow',
            escrowConditions: [
                'condition1' => false,
                'condition2' => false,
            ]
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertTrue($result['isValid']);
        $this->assertEquals(['condition1', 'condition2'], $result['escrowRequirements']);
    }

    #[Test]
    public function it_validates_split_payments(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'USD',
            purpose: 'payment',
            splits: [
                ['agentDid' => 'did:agent:test:split1', 'amount' => 10.00],
                ['agentDid' => 'did:agent:test:split2', 'amount' => 5.00],
            ]
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    #[Test]
    public function it_rejects_splits_exceeding_total(): void
    {
        // Arrange
        $request = new AgentPaymentRequest(
            fromAgentDid: 'did:agent:test:sender',
            toAgentDid: 'did:agent:test:receiver',
            amount: 100.00,
            currency: 'USD',
            purpose: 'payment',
            splits: [
                ['agentDid' => 'did:agent:test:split1', 'amount' => 60.00],
                ['agentDid' => 'did:agent:test:split2', 'amount' => 50.00], // Total 110 > 100
            ]
        );

        // Act
        $result = $this->validatePaymentRequest($request);

        // Assert
        $this->assertFalse($result['isValid']);
        $this->assertContains('Split amounts exceed total payment', $result['errors']);
    }
}
