<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services\HyperSwitch;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * HTTP client for the HyperSwitch Payment Orchestration API.
 *
 * Provides type-safe methods for creating payments, managing refunds,
 * listing connectors, and interacting with the HyperSwitch REST API.
 *
 * @see https://api-reference.hyperswitch.io
 */
class HyperSwitchClient
{
    private readonly string $baseUrl;

    private readonly string $apiKey;

    private readonly int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('hyperswitch.base_url', ''), '/');
        $this->apiKey = (string) config('hyperswitch.api_key', '');
        $this->timeout = (int) config('hyperswitch.timeout', 30);
    }

    // ────────────────────────────────────────────────────────────
    //  Payments
    // ────────────────────────────────────────────────────────────

    /**
     * Create a payment.
     *
     * @param array<string, mixed> $data Payment data (amount, currency, etc.)
     *
     * @return array<string, mixed> HyperSwitch payment response
     */
    public function createPayment(array $data): array
    {
        $payload = array_merge([
            'currency'            => config('hyperswitch.defaults.currency', 'EUR'),
            'capture_method'      => config('hyperswitch.defaults.capture_method', 'automatic'),
            'authentication_type' => config('hyperswitch.defaults.authentication_type', 'three_ds'),
        ], $data);

        if (! empty(config('hyperswitch.defaults.return_url')) && ! isset($payload['return_url'])) {
            $payload['return_url'] = config('hyperswitch.defaults.return_url');
        }

        if (! empty(config('hyperswitch.profile_id')) && ! isset($payload['profile_id'])) {
            $payload['profile_id'] = config('hyperswitch.profile_id');
        }

        return $this->post('/payments', $payload);
    }

    /**
     * Retrieve a payment by ID.
     *
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get("/payments/{$paymentId}");
    }

    /**
     * Confirm a payment (if created with confirm=false).
     *
     * @param array<string, mixed> $data Confirmation data
     *
     * @return array<string, mixed>
     */
    public function confirmPayment(string $paymentId, array $data = []): array
    {
        return $this->post("/payments/{$paymentId}/confirm", $data);
    }

    /**
     * Capture a manually-authorized payment.
     *
     * @return array<string, mixed>
     */
    public function capturePayment(string $paymentId, int $amountToCapture): array
    {
        return $this->post("/payments/{$paymentId}/capture", [
            'amount_to_capture' => $amountToCapture,
        ]);
    }

    /**
     * Cancel a payment.
     *
     * @return array<string, mixed>
     */
    public function cancelPayment(string $paymentId, string $reason = ''): array
    {
        return $this->post("/payments/{$paymentId}/cancel", array_filter([
            'cancellation_reason' => $reason,
        ]));
    }

    /**
     * List payments with optional filters.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function listPayments(array $filters = []): array
    {
        return $this->get('/payments/list', $filters);
    }

    // ────────────────────────────────────────────────────────────
    //  Refunds
    // ────────────────────────────────────────────────────────────

    /**
     * Create a refund.
     *
     * @return array<string, mixed>
     */
    public function createRefund(string $paymentId, int $amount, string $reason = ''): array
    {
        return $this->post('/refunds', array_filter([
            'payment_id' => $paymentId,
            'amount'     => $amount,
            'reason'     => $reason,
        ]));
    }

    /**
     * Retrieve a refund.
     *
     * @return array<string, mixed>
     */
    public function getRefund(string $refundId): array
    {
        return $this->get("/refunds/{$refundId}");
    }

    // ────────────────────────────────────────────────────────────
    //  Connectors
    // ────────────────────────────────────────────────────────────

    /**
     * List all configured payment connectors.
     *
     * @return array<string, mixed>
     */
    public function listConnectors(): array
    {
        $merchantId = $this->getMerchantId();

        if ($merchantId === '') {
            return [];
        }

        return $this->get("/account/{$merchantId}/connectors");
    }

    // ────────────────────────────────────────────────────────────
    //  Customers
    // ────────────────────────────────────────────────────────────

    /**
     * Create a customer in HyperSwitch.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function createCustomer(array $data): array
    {
        return $this->post('/customers', $data);
    }

    /**
     * Retrieve a customer.
     *
     * @return array<string, mixed>
     */
    public function getCustomer(string $customerId): array
    {
        return $this->get("/customers/{$customerId}");
    }

    // ────────────────────────────────────────────────────────────
    //  Health / Status
    // ────────────────────────────────────────────────────────────

    /**
     * Check HyperSwitch health.
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health");

            return [
                'available' => $response->successful(),
                'status'    => $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'error'     => $e->getMessage(),
            ];
        }
    }

    // ────────────────────────────────────────────────────────────
    //  HTTP Layer
    // ────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $response = $this->request()->get("{$this->baseUrl}{$path}", $query);

        return $this->handleResponse($response, 'GET', $path);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function post(string $path, array $data = []): array
    {
        $response = $this->request()->post("{$this->baseUrl}{$path}", $data);

        return $this->handleResponse($response, 'POST', $path);
    }

    private function request(): PendingRequest
    {
        $this->ensureConfigured();

        return Http::timeout($this->timeout)
            ->withHeaders([
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleResponse(Response $response, string $method, string $path): array
    {
        if ($response->successful()) {
            /** @var array<string, mixed> $json */
            $json = $response->json() ?? [];

            return $json;
        }

        Log::error('HyperSwitch: API error', [
            'method' => $method,
            'path'   => $path,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new RuntimeException(
            "HyperSwitch API error ({$response->status()}): " . ($response->json('error.message') ?? $response->body())
        );
    }

    private function ensureConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('HyperSwitch API key not configured. Set HYPERSWITCH_API_KEY in .env');
        }
    }

    private function getMerchantId(): string
    {
        // Extract merchant ID from API key prefix or use config
        return (string) config('hyperswitch.profile_id', '');
    }
}
