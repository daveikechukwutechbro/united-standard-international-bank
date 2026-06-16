<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentServiceInterface;
use App\Domain\Payment\Exceptions\InsufficientFundsException;
use App\Domain\Payment\Exceptions\InvalidPaymentMethodException;
use App\Domain\Payment\Exceptions\PaymentCancellationException;
use App\Domain\Payment\Exceptions\PaymentNotFoundException;
use App\Domain\Payment\Exceptions\PaymentProcessingException;
use App\Domain\Payment\Exceptions\RefundNotAllowedException;
use App\Domain\Shared\Contracts\AccountOperationsInterface;
use App\Domain\Shared\Contracts\PaymentProcessingInterface;
use App\Domain\Shared\Logging\AuditLogger;
use App\Domain\Shared\Validation\FinancialInputValidator;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentProcessingService implements PaymentProcessingInterface
{
    use FinancialInputValidator;
    use AuditLogger;

    private const PAYMENT_PREFIX = 'payment:';

    private const PAYMENT_TTL = 86400 * 30; // 30 days

    private const VALID_PAYMENT_METHODS = [
        'stripe',
        'open_banking',
        'bank_transfer',
        'paysera',
        'crypto',
    ];

    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly AccountOperationsInterface $accountOperations
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processDeposit(
        string $accountId,
        int $amount,
        string $currency,
        string $paymentMethod,
        string $reference = '',
        array $metadata = []
    ): array {
        // Input validation
        $this->validateUuid($accountId, 'account ID');
        $this->validatePositiveIntegerAmount($amount);
        $this->validateCurrencyCode($currency);
        $this->validatePaymentMethodInput($paymentMethod);
        $this->validateReference($reference);
        $this->validateMetadata($metadata);

        $this->auditOperationStart('payment_deposit', [
            'account_id'     => $accountId,
            'amount'         => $amount,
            'currency'       => $currency,
            'payment_method' => $paymentMethod,
        ]);

        $paymentId = (string) Str::uuid();

        try {
            // Route to appropriate payment processor
            $externalReference = match ($paymentMethod) {
                'stripe' => $this->paymentService->processStripeDeposit([
                    'account_uuid'        => $accountId,
                    'amount'              => $amount,
                    'currency'            => $currency,
                    'reference'           => $reference ?: $paymentId,
                    'external_reference'  => $paymentId,
                    'payment_method'      => $paymentMethod,
                    'payment_method_type' => 'card',
                    'metadata'            => $metadata,
                ]),
                'open_banking' => $this->paymentService->processOpenBankingDeposit([
                    'account_uuid' => $accountId,
                    'amount'       => $amount,
                    'currency'     => $currency,
                    'reference'    => $reference ?: $paymentId,
                    'bank_name'    => $metadata['bank_name'] ?? 'unknown',
                    'metadata'     => $metadata,
                ]),
                default => $paymentId, // Demo/fallback
            };

            $payment = [
                'payment_id'         => $paymentId,
                'status'             => 'completed',
                'external_reference' => $externalReference,
                'amount'             => $amount,
                'currency'           => $currency,
                'type'               => 'deposit',
                'account_id'         => $accountId,
                'payment_method'     => $paymentMethod,
                'created_at'         => now()->toIso8601String(),
            ];

            // Store payment record with encryption
            Cache::put(self::PAYMENT_PREFIX . $paymentId, encrypt($payment), self::PAYMENT_TTL);

            $this->auditOperationSuccess('payment_deposit', [
                'payment_id' => $paymentId,
                'account_id' => $accountId,
                'amount'     => $amount,
                'currency'   => $currency,
            ]);

            return $payment;
        } catch (Exception $e) {
            $this->auditOperationFailure('payment_deposit', $e->getMessage(), [
                'account_id'     => $accountId,
                'payment_method' => $paymentMethod,
            ]);
            throw new PaymentProcessingException($paymentMethod, $paymentId, $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function processWithdrawal(
        string $accountId,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $destination,
        string $reference = '',
        array $metadata = []
    ): array {
        // Input validation
        $this->validateUuid($accountId, 'account ID');
        $this->validatePositiveIntegerAmount($amount);
        $this->validateCurrencyCode($currency);
        $this->validatePaymentMethodInput($paymentMethod);
        $this->validateReference($reference);
        $this->validateMetadata($metadata);

        $this->auditOperationStart('payment_withdrawal', [
            'account_id'     => $accountId,
            'amount'         => $amount,
            'currency'       => $currency,
            'payment_method' => $paymentMethod,
        ]);

        // Check sufficient balance
        $balance = (int) $this->accountOperations->getBalance($accountId, $currency);
        if ($balance < $amount) {
            $this->auditOperationFailure('payment_withdrawal', 'Insufficient funds', [
                'account_id' => $accountId,
                'required'   => $amount,
                'available'  => $balance,
            ]);
            throw new InsufficientFundsException($accountId, $amount, $balance, $currency);
        }

        $paymentId = (string) Str::uuid();

        try {
            // Route to appropriate payment processor
            $result = match ($paymentMethod) {
                'bank_transfer' => $this->paymentService->processBankWithdrawal([
                    'account_uuid'        => $accountId,
                    'amount'              => $amount,
                    'currency'            => $currency,
                    'reference'           => $reference ?: $paymentId,
                    'bank_name'           => $destination['bank_name'] ?? '',
                    'account_number'      => $destination['account_number'] ?? '',
                    'account_holder_name' => $destination['account_holder_name'] ?? '',
                    'routing_number'      => $destination['routing_number'] ?? null,
                    'iban'                => $destination['iban'] ?? null,
                    'swift'               => $destination['swift'] ?? null,
                    'metadata'            => $metadata,
                ]),
                default => ['reference' => $paymentId, 'status' => 'pending'],
            };

            $payment = [
                'payment_id'         => $paymentId,
                'status'             => $result['status'] ?? 'pending',
                'external_reference' => $result['reference'] ?? null,
                'amount'             => $amount,
                'currency'           => $currency,
                'type'               => 'withdrawal',
                'account_id'         => $accountId,
                'payment_method'     => $paymentMethod,
                'estimated_arrival'  => now()->addDays(3)->toIso8601String(),
                'created_at'         => now()->toIso8601String(),
            ];

            Cache::put(self::PAYMENT_PREFIX . $paymentId, encrypt($payment), self::PAYMENT_TTL);

            $this->auditOperationSuccess('payment_withdrawal', [
                'payment_id' => $paymentId,
                'account_id' => $accountId,
                'amount'     => $amount,
                'currency'   => $currency,
            ]);

            return $payment;
        } catch (Exception $e) {
            $this->auditOperationFailure('payment_withdrawal', $e->getMessage(), [
                'account_id'     => $accountId,
                'payment_method' => $paymentMethod,
            ]);
            throw new PaymentProcessingException($paymentMethod, $paymentId, $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentStatus(string $paymentId): ?array
    {
        $this->validateUuid($paymentId, 'payment ID');

        $encryptedPayment = Cache::get(self::PAYMENT_PREFIX . $paymentId);

        if (! $encryptedPayment) {
            return null;
        }

        $payment = decrypt($encryptedPayment);

        return [
            'payment_id'         => $payment['payment_id'],
            'status'             => $payment['status'],
            'type'               => $payment['type'],
            'account_id'         => $payment['account_id'],
            'amount'             => $payment['amount'],
            'currency'           => $payment['currency'],
            'payment_method'     => $payment['payment_method'],
            'external_reference' => $payment['external_reference'] ?? null,
            'created_at'         => $payment['created_at'],
            'updated_at'         => $payment['updated_at'] ?? $payment['created_at'],
            'completed_at'       => $payment['completed_at'] ?? null,
            'failure_reason'     => $payment['failure_reason'] ?? null,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function refundPayment(
        string $paymentId,
        ?int $amount = null,
        string $reason = '',
        array $metadata = []
    ): array {
        $this->validateUuid($paymentId, 'payment ID');

        if ($amount !== null) {
            $this->validatePositiveIntegerAmount($amount);
        }

        $this->validateReference($reason, 'reason');
        $this->validateMetadata($metadata);

        $this->auditOperationStart('payment_refund', [
            'payment_id' => $paymentId,
            'amount'     => $amount,
        ]);

        $encryptedPayment = Cache::get(self::PAYMENT_PREFIX . $paymentId);

        if (! $encryptedPayment) {
            $this->auditOperationFailure('payment_refund', 'Payment not found', [
                'payment_id' => $paymentId,
            ]);
            throw new PaymentNotFoundException($paymentId);
        }

        $payment = decrypt($encryptedPayment);

        if ($payment['type'] !== 'deposit') {
            $this->auditOperationFailure('payment_refund', 'Only deposits can be refunded', [
                'payment_id'   => $paymentId,
                'payment_type' => $payment['type'],
            ]);
            throw new RefundNotAllowedException($paymentId, 'Only deposits can be refunded');
        }

        if ($payment['status'] !== 'completed') {
            $this->auditOperationFailure('payment_refund', 'Payment not completed', [
                'payment_id'     => $paymentId,
                'payment_status' => $payment['status'],
            ]);
            throw new RefundNotAllowedException($paymentId, 'Payment must be completed to refund');
        }

        $refundAmount = $amount ?? $payment['amount'];
        $refundId = (string) Str::uuid();

        $refund = [
            'refund_id'  => $refundId,
            'payment_id' => $paymentId,
            'status'     => 'completed',
            'amount'     => $refundAmount,
            'currency'   => $payment['currency'],
            'created_at' => now()->toIso8601String(),
        ];

        // Update original payment
        $payment['status'] = $refundAmount >= $payment['amount'] ? 'refunded' : 'partially_refunded';
        $payment['updated_at'] = now()->toIso8601String();
        Cache::put(self::PAYMENT_PREFIX . $paymentId, encrypt($payment), self::PAYMENT_TTL);

        $this->auditOperationSuccess('payment_refund', [
            'refund_id'     => $refundId,
            'payment_id'    => $paymentId,
            'refund_amount' => $refundAmount,
        ]);

        return $refund;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePaymentRequest(
        string $type,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $context = []
    ): array {
        $errors = [];
        $warnings = [];

        // Validate payment method
        if (! in_array($paymentMethod, self::VALID_PAYMENT_METHODS, true)) {
            $errors[] = "Invalid payment method: {$paymentMethod}";
        }

        // Validate amount
        if ($amount <= 0) {
            $errors[] = 'Amount must be greater than 0';
        }

        // Validate currency
        try {
            $this->validateCurrencyCode($currency);
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        $limits = $this->getPaymentLimits($paymentMethod, $type);

        if ($amount < $limits['min_amount']) {
            $errors[] = "Amount below minimum: {$limits['min_amount']}";
        }

        if ($amount > $limits['max_amount']) {
            $errors[] = "Amount exceeds maximum: {$limits['max_amount']}";
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
            'limits'   => $limits,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailablePaymentMethods(string $accountId, string $type = 'all'): array
    {
        $this->validateUuid($accountId, 'account ID');

        $methods = [
            [
                'id'         => 'stripe',
                'name'       => 'Credit/Debit Card',
                'type'       => 'card',
                'currencies' => ['USD', 'EUR', 'GBP'],
                'min_amount' => 100, // $1.00
                'max_amount' => 10000000, // $100,000
                'fees'       => ['fixed' => 30, 'percentage' => 2.9],
                'is_enabled' => true,
            ],
            [
                'id'         => 'open_banking',
                'name'       => 'Bank Transfer (Open Banking)',
                'type'       => 'bank',
                'currencies' => ['EUR', 'GBP'],
                'min_amount' => 1000, // $10.00
                'max_amount' => 100000000, // $1,000,000
                'fees'       => ['fixed' => 0, 'percentage' => 0.1],
                'is_enabled' => true,
            ],
            [
                'id'         => 'bank_transfer',
                'name'       => 'Wire Transfer',
                'type'       => 'bank',
                'currencies' => ['USD', 'EUR', 'GBP'],
                'min_amount' => 10000, // $100.00
                'max_amount' => 1000000000, // $10,000,000
                'fees'       => ['fixed' => 2500, 'percentage' => 0],
                'is_enabled' => true,
            ],
        ];

        if ($type !== 'all') {
            $methods = array_filter($methods, fn ($m) => match ($type) {
                'deposit'    => in_array($m['id'], ['stripe', 'open_banking', 'bank_transfer']),
                'withdrawal' => in_array($m['id'], ['bank_transfer', 'open_banking']),
                default      => true,
            });
        }

        return array_values($methods);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateFees(
        int $amount,
        string $currency,
        string $paymentMethod,
        string $type
    ): array {
        $this->validatePositiveIntegerAmount($amount);
        $this->validateCurrencyCode($currency);

        $fees = match ($paymentMethod) {
            'stripe'        => ['fixed' => 30, 'percentage' => 2.9],
            'open_banking'  => ['fixed' => 0, 'percentage' => 0.1],
            'bank_transfer' => ['fixed' => 2500, 'percentage' => 0],
            default         => ['fixed' => 0, 'percentage' => 0],
        };

        $fixedFee = $fees['fixed'];
        $percentageFee = (int) ceil($amount * $fees['percentage'] / 100);
        $totalFee = $fixedFee + $percentageFee;

        return [
            'gross_amount'  => $amount,
            'fee_amount'    => $totalFee,
            'net_amount'    => $amount - $totalFee,
            'fee_breakdown' => [
                'fixed'      => $fixedFee,
                'percentage' => $percentageFee,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function canCancelPayment(string $paymentId): bool
    {
        $this->validateUuid($paymentId, 'payment ID');

        $encryptedPayment = Cache::get(self::PAYMENT_PREFIX . $paymentId);

        if (! $encryptedPayment) {
            return false;
        }

        $payment = decrypt($encryptedPayment);

        return in_array($payment['status'], ['pending', 'processing'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelPayment(string $paymentId, string $reason = ''): bool
    {
        $this->validateUuid($paymentId, 'payment ID');
        $this->validateReference($reason, 'reason');

        $this->auditOperationStart('payment_cancel', ['payment_id' => $paymentId]);

        $encryptedPayment = Cache::get(self::PAYMENT_PREFIX . $paymentId);

        if (! $encryptedPayment) {
            $this->auditOperationFailure('payment_cancel', 'Payment not found', [
                'payment_id' => $paymentId,
            ]);
            throw new PaymentNotFoundException($paymentId);
        }

        $payment = decrypt($encryptedPayment);

        if (! $this->canCancelPayment($paymentId)) {
            $this->auditOperationFailure('payment_cancel', 'Cannot cancel payment', [
                'payment_id'     => $paymentId,
                'payment_status' => $payment['status'],
            ]);
            throw new PaymentCancellationException(
                $paymentId,
                "Payment in status '{$payment['status']}' cannot be cancelled"
            );
        }

        $payment['status'] = 'cancelled';
        $payment['updated_at'] = now()->toIso8601String();
        $payment['failure_reason'] = $reason ?: 'Cancelled by user';

        Cache::put(self::PAYMENT_PREFIX . $paymentId, encrypt($payment), self::PAYMENT_TTL);

        $this->auditOperationSuccess('payment_cancel', ['payment_id' => $paymentId]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentHistory(
        string $accountId,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array {
        $this->validateUuid($accountId, 'account ID');

        // Validate and sanitize pagination
        $limit = max(1, min(100, $limit)); // Limit between 1-100
        $offset = max(0, $offset);

        // Note: In production, this would query a database
        // For now, return empty as payments are stored in cache
        return [
            'payments' => [],
            'total'    => 0,
            'has_more' => false,
        ];
    }

    /**
     * Validate payment method.
     */
    private function validatePaymentMethodInput(string $paymentMethod): void
    {
        if (! in_array($paymentMethod, self::VALID_PAYMENT_METHODS, true)) {
            throw new InvalidPaymentMethodException($paymentMethod);
        }
    }

    /**
     * Get payment limits for a method.
     *
     * @return array{min_amount: int, max_amount: int, daily_remaining: int|null}
     */
    private function getPaymentLimits(string $paymentMethod, string $type): array
    {
        return match ($paymentMethod) {
            'stripe' => [
                'min_amount'      => 100,
                'max_amount'      => 10000000,
                'daily_remaining' => null,
            ],
            'open_banking' => [
                'min_amount'      => 1000,
                'max_amount'      => 100000000,
                'daily_remaining' => null,
            ],
            'bank_transfer' => [
                'min_amount'      => 10000,
                'max_amount'      => 1000000000,
                'daily_remaining' => null,
            ],
            default => [
                'min_amount'      => 100,
                'max_amount'      => 1000000,
                'daily_remaining' => null,
            ],
        };
    }
}
