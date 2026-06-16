<?php

declare(strict_types=1);

namespace Plugins\WebhookNotifier;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookHookListener
{
    /**
     * @param  array<int, string>  $events
     */
    public function __construct(
        private readonly string $webhookUrl,
        private readonly ?string $secret = null,
        private readonly array $events = [],
    ) {}

    /**
     * Handle a hook event by sending a webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $hookName, array $payload): void
    {
        if (empty($this->webhookUrl)) {
            return;
        }

        // Filter by configured events if set
        if (! empty($this->events) && ! in_array($hookName, $this->events, true)) {
            return;
        }

        $body = [
            'event'      => $hookName,
            'payload'    => $payload,
            'timestamp'  => now()->toIso8601String(),
            'source'     => 'finaegis',
        ];

        $headers = ['Content-Type' => 'application/json'];

        if ($this->secret) {
            $signature = hash_hmac('sha256', json_encode($body) ?: '', $this->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            Http::withHeaders($headers)
                ->timeout(10)
                ->post($this->webhookUrl, $body);

            Log::info("Webhook sent for {$hookName}", ['url' => $this->webhookUrl]);
        } catch (\Throwable $e) {
            Log::error("Webhook delivery failed for {$hookName}", [
                'url'   => $this->webhookUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
