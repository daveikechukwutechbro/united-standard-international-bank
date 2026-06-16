<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Compliance\Services\OndatoService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOndatoWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $payload,
        public readonly string $webhookType
    ) {
    }

    public function handle(OndatoService $ondatoService): void
    {
        try {
            Log::info('Processing Ondato webhook job', [
                'event_type'   => $this->eventType,
                'webhook_type' => $this->webhookType,
                'payload_id'   => $this->payload['id'] ?? null,
            ]);

            $ondatoService->processWebhook($this->eventType, $this->payload);
        } catch (Exception $e) {
            Log::error('Failed to process Ondato webhook', [
                'event_type'   => $this->eventType,
                'webhook_type' => $this->webhookType,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['webhook', 'ondato', $this->webhookType];
    }
}
