<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Services;

use Illuminate\Support\Str;

final class WebhookTestService
{
    /** @var array<string, array<string, mixed>> */
    private const EVENT_TEMPLATES = [
        'payment.completed' => [
            'event' => 'payment.completed',
            'data'  => ['payment_id' => '', 'amount' => '100.00', 'currency' => 'USD', 'status' => 'completed'],
        ],
        'transfer.initiated' => [
            'event' => 'transfer.initiated',
            'data'  => ['transfer_id' => '', 'amount' => '500.00', 'currency' => 'EUR', 'rail' => 'SEPA'],
        ],
        'account.created' => [
            'event' => 'account.created',
            'data'  => ['account_id' => '', 'type' => 'checking', 'currency' => 'USD'],
        ],
        'consent.authorized' => [
            'event' => 'consent.authorized',
            'data'  => ['consent_id' => '', 'tpp_id' => 'TPP-001', 'permissions' => ['ReadBalances']],
        ],
        'card.authorization' => [
            'event' => 'card.authorization',
            'data'  => ['card_id' => '', 'amount' => '25.00', 'currency' => 'USD', 'merchant' => 'Test Store'],
        ],
    ];

    /**
     * Generate a test webhook payload for a given event type.
     *
     * @return array{event: string, data: array<string, mixed>, timestamp: string, webhook_id: string}
     */
    public function generateTestPayload(string $eventType): array
    {
        $template = self::EVENT_TEMPLATES[$eventType] ?? [
            'event' => $eventType,
            'data'  => ['message' => 'Test event'],
        ];

        // Fill in dynamic IDs
        $data = $template['data'];
        foreach ($data as $key => $value) {
            if ($value === '' && str_ends_with($key, '_id')) {
                $data[$key] = Str::uuid()->toString();
            }
        }

        return [
            'event'      => $template['event'],
            'data'       => $data,
            'timestamp'  => now()->toIso8601String(),
            'webhook_id' => 'whk_test_' . Str::random(16),
        ];
    }

    /**
     * Get all available test event types.
     *
     * @return array<string>
     */
    public function getAvailableEvents(): array
    {
        return array_keys(self::EVENT_TEMPLATES);
    }
}
