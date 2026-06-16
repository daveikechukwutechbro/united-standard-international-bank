<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WebhookReplayService
{
    /**
     * Replay a past webhook delivery.
     *
     * @return array{replayed: bool, delivery_id: string, status_code: int|null, error: string|null}
     */
    public function replay(string $deliveryId, string $targetUrl, string $payload, ?string $secret = null): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($secret !== null) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', $payload, $secret);
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->withBody($payload, 'application/json')
                ->post($targetUrl);

            Log::info('Webhook replayed', [
                'delivery_id' => $deliveryId,
                'status_code' => $response->status(),
            ]);

            return [
                'replayed'    => true,
                'delivery_id' => $deliveryId,
                'status_code' => $response->status(),
                'error'       => null,
            ];
        } catch (Exception $e) {
            return [
                'replayed'    => false,
                'delivery_id' => $deliveryId,
                'status_code' => null,
                'error'       => $e->getMessage(),
            ];
        }
    }
}
