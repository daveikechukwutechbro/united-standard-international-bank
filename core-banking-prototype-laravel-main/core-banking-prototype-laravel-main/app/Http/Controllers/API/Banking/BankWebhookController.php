<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Banking;

use App\Domain\Banking\Services\BankTransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankWebhookController extends Controller
{
    public function __construct(
        private readonly BankTransferService $bankTransferService,
    ) {
    }

    /**
     * Handle transfer status update webhooks from bank providers.
     */
    public function transferUpdate(Request $request, string $provider): JsonResponse
    {
        if (! $this->verifySignature($request, $provider)) {
            Log::warning('Bank webhook signature verification failed', [
                'provider' => $provider,
                'ip'       => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload.'], 400);
        }

        $transferId = (string) ($payload['transfer_id'] ?? $payload['id'] ?? '');
        $newStatus = (string) ($payload['status'] ?? $payload['state'] ?? '');
        $note = (string) ($payload['message'] ?? $payload['reason'] ?? "Webhook update from {$provider}");

        if ($transferId === '' || $newStatus === '') {
            Log::warning('Bank webhook missing required fields', [
                'provider' => $provider,
                'payload'  => $payload,
            ]);

            return response()->json(['error' => 'Missing transfer_id or status.'], 400);
        }

        $statusMap = [
            'succeeded'  => 'completed',
            'success'    => 'completed',
            'completed'  => 'completed',
            'failed'     => 'failed',
            'error'      => 'failed',
            'rejected'   => 'failed',
            'processing' => 'processing',
            'pending'    => 'pending',
            'cancelled'  => 'cancelled',
            'canceled'   => 'cancelled',
        ];

        $mappedStatus = $statusMap[strtolower($newStatus)] ?? $newStatus;

        $advanced = $this->bankTransferService->advanceStatus($transferId, $mappedStatus, $note);

        Log::info('Bank transfer webhook processed', [
            'provider'    => $provider,
            'transfer_id' => $transferId,
            'new_status'  => $mappedStatus,
            'advanced'    => $advanced,
        ]);

        return response()->json([
            'status'  => 'accepted',
            'applied' => $advanced,
        ], 202);
    }

    /**
     * Handle account status update webhooks from bank providers.
     */
    public function accountUpdate(Request $request, string $provider): JsonResponse
    {
        if (! $this->verifySignature($request, $provider)) {
            Log::warning('Bank webhook signature verification failed', [
                'provider' => $provider,
                'ip'       => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload.'], 400);
        }

        Log::info('Bank account webhook received', [
            'provider'   => $provider,
            'event_type' => $payload['event_type'] ?? $payload['type'] ?? 'unknown',
            'account_id' => $payload['account_id'] ?? $payload['id'] ?? 'unknown',
        ]);

        return response()->json([
            'status' => 'accepted',
        ], 202);
    }

    /**
     * Verify HMAC signature from the X-Webhook-Signature header.
     */
    private function verifySignature(Request $request, string $provider): bool
    {
        $signature = $request->header('X-Webhook-Signature', '');

        if ($signature === '') {
            return false;
        }

        $secret = (string) config("services.banking.webhooks.{$provider}.secret", '');

        if ($secret === '') {
            // Fall back to a global webhook secret
            $secret = (string) config('services.banking.webhooks.secret', '');
        }

        if ($secret === '') {
            Log::warning('No webhook secret configured for bank provider', [
                'provider' => $provider,
            ]);

            // In non-production, allow unsigned webhooks for development convenience
            if (! app()->environment('production')) {
                return true;
            }

            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
