<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Interface for payment processing operations used by external domains.
 *
 * This interface enables domain decoupling by allowing domains like
 * Exchange, AgentProtocol, Stablecoin, etc. to depend on an abstraction
 * rather than the concrete Payment domain implementation.
 *
 * Provides a unified interface for deposits, withdrawals, and refunds
 * regardless of the underlying payment provider (Stripe, OpenBanking, etc.).
 *
 * All amounts are in the smallest currency unit (cents for fiat).
 *
 * @see \App\Domain\Payment\Services\PaymentService for implementation
 */
interface PaymentProcessingInterface
{
    /**
     * Process a deposit into an account.
     *
     * @param string $accountId Account UUID to receive the deposit
     * @param int $amount Amount in smallest currency unit (e.g., cents)
     * @param string $currency Currency code (e.g., 'USD', 'EUR')
     * @param string $paymentMethod Payment method identifier
     * @param string $reference Transaction reference
     * @param array<string, mixed> $metadata Additional payment metadata
     * @return array{
     *     payment_id: string,
     *     status: string,
     *     external_reference: string|null,
     *     amount: int,
     *     currency: string,
     *     created_at: string
     * }
     *
     * @throws \App\Domain\Payment\Exceptions\PaymentProcessingException On processing failure
     * @throws \App\Domain\Payment\Exceptions\InvalidPaymentMethodException For unsupported methods
     */
    public function processDeposit(
        string $accountId,
        int $amount,
        string $currency,
        string $paymentMethod,
        string $reference = '',
        array $metadata = []
    ): array;

    /**
     * Process a withdrawal from an account.
     *
     * @param string $accountId Account UUID to withdraw from
     * @param int $amount Amount in smallest currency unit
     * @param string $currency Currency code
     * @param string $paymentMethod Payment method identifier
     * @param array<string, mixed> $destination Destination details (bank account, wallet, etc.)
     * @param string $reference Transaction reference
     * @param array<string, mixed> $metadata Additional payment metadata
     * @return array{
     *     payment_id: string,
     *     status: string,
     *     external_reference: string|null,
     *     amount: int,
     *     currency: string,
     *     estimated_arrival: string|null,
     *     created_at: string
     * }
     *
     * @throws \App\Domain\Payment\Exceptions\PaymentProcessingException On processing failure
     * @throws \App\Domain\Payment\Exceptions\InsufficientFundsException When balance is insufficient
     */
    public function processWithdrawal(
        string $accountId,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $destination,
        string $reference = '',
        array $metadata = []
    ): array;

    /**
     * Get the status of a payment.
     *
     * @param string $paymentId Payment UUID
     * @return array{
     *     payment_id: string,
     *     status: string,
     *     type: string,
     *     account_id: string,
     *     amount: int,
     *     currency: string,
     *     payment_method: string,
     *     external_reference: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     completed_at: string|null,
     *     failure_reason: string|null
     * }|null Payment details or null if not found
     */
    public function getPaymentStatus(string $paymentId): ?array;

    /**
     * Process a refund for a payment.
     *
     * @param string $paymentId Original payment UUID
     * @param int|null $amount Amount to refund (null for full refund)
     * @param string $reason Refund reason
     * @param array<string, mixed> $metadata Additional refund metadata
     * @return array{
     *     refund_id: string,
     *     payment_id: string,
     *     status: string,
     *     amount: int,
     *     currency: string,
     *     created_at: string
     * }
     *
     * @throws \App\Domain\Payment\Exceptions\PaymentNotFoundException When payment is not found
     * @throws \App\Domain\Payment\Exceptions\RefundNotAllowedException When refund is not allowed
     */
    public function refundPayment(
        string $paymentId,
        ?int $amount = null,
        string $reason = '',
        array $metadata = []
    ): array;

    /**
     * Validate a payment request before processing.
     *
     * @param string $type Payment type ('deposit', 'withdrawal')
     * @param int $amount Amount in smallest currency unit
     * @param string $currency Currency code
     * @param string $paymentMethod Payment method identifier
     * @param array<string, mixed> $context Additional validation context
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>,
     *     limits: array{
     *         min_amount: int,
     *         max_amount: int,
     *         daily_remaining: int|null
     *     }|null
     * }
     */
    public function validatePaymentRequest(
        string $type,
        int $amount,
        string $currency,
        string $paymentMethod,
        array $context = []
    ): array;

    /**
     * Get available payment methods for an account.
     *
     * @param string $accountId Account UUID
     * @param string $type Payment type ('deposit', 'withdrawal', 'all')
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     type: string,
     *     currencies: array<int, string>,
     *     min_amount: int|null,
     *     max_amount: int|null,
     *     fees: array{
     *         fixed: int,
     *         percentage: float
     *     }|null,
     *     is_enabled: bool
     * }>
     */
    public function getAvailablePaymentMethods(string $accountId, string $type = 'all'): array;

    /**
     * Calculate fees for a payment.
     *
     * @param int $amount Amount in smallest currency unit
     * @param string $currency Currency code
     * @param string $paymentMethod Payment method identifier
     * @param string $type Payment type ('deposit', 'withdrawal')
     * @return array{
     *     gross_amount: int,
     *     fee_amount: int,
     *     net_amount: int,
     *     fee_breakdown: array<string, int>
     * }
     */
    public function calculateFees(
        int $amount,
        string $currency,
        string $paymentMethod,
        string $type
    ): array;

    /**
     * Check if a payment can be cancelled.
     *
     * @param string $paymentId Payment UUID
     * @return bool True if payment can be cancelled
     */
    public function canCancelPayment(string $paymentId): bool;

    /**
     * Cancel a pending payment.
     *
     * @param string $paymentId Payment UUID
     * @param string $reason Cancellation reason
     * @return bool True if cancelled successfully
     *
     * @throws \App\Domain\Payment\Exceptions\PaymentNotFoundException When payment is not found
     * @throws \App\Domain\Payment\Exceptions\PaymentCancellationException When cancellation fails
     */
    public function cancelPayment(string $paymentId, string $reason = ''): bool;

    /**
     * Get payment history for an account.
     *
     * @param string $accountId Account UUID
     * @param array<string, mixed> $filters Optional filters (type, status, date_from, date_to)
     * @param int $limit Maximum number of results
     * @param int $offset Pagination offset
     * @return array{
     *     payments: array<int, array{
     *         payment_id: string,
     *         type: string,
     *         status: string,
     *         amount: int,
     *         currency: string,
     *         payment_method: string,
     *         created_at: string
     *     }>,
     *     total: int,
     *     has_more: bool
     * }
     */
    public function getPaymentHistory(
        string $accountId,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array;
}
