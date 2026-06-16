<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Webhook\Services;

use App\Domain\Webhook\Services\WebhookTestService;
use Tests\TestCase;

class WebhookTestServiceTest extends TestCase
{
    private WebhookTestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookTestService();
    }

    public function test_get_available_events_returns_expected_events(): void
    {
        $events = $this->service->getAvailableEvents();

        $this->assertContains('payment.completed', $events);
        $this->assertContains('transfer.initiated', $events);
        $this->assertContains('account.created', $events);
        $this->assertContains('consent.authorized', $events);
        $this->assertContains('card.authorization', $events);
    }

    public function test_get_available_events_returns_array_of_strings(): void
    {
        $events = $this->service->getAvailableEvents();

        $this->assertIsArray($events);
        foreach ($events as $event) {
            $this->assertIsString($event);
        }
    }

    public function test_generate_test_payload_returns_correct_structure(): void
    {
        $payload = $this->service->generateTestPayload('payment.completed');

        $this->assertArrayHasKey('event', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('webhook_id', $payload);
    }

    public function test_generate_test_payload_has_correct_event_type(): void
    {
        $payload = $this->service->generateTestPayload('payment.completed');

        $this->assertEquals('payment.completed', $payload['event']);
    }

    public function test_generate_test_payload_fills_in_uuids(): void
    {
        $payload = $this->service->generateTestPayload('payment.completed');

        $this->assertNotEmpty($payload['data']['payment_id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $payload['data']['payment_id']
        );
    }

    public function test_generate_test_payload_webhook_id_has_test_prefix(): void
    {
        $payload = $this->service->generateTestPayload('payment.completed');

        $this->assertStringStartsWith('whk_test_', $payload['webhook_id']);
    }

    public function test_generate_test_payload_has_iso8601_timestamp(): void
    {
        $payload = $this->service->generateTestPayload('payment.completed');

        $this->assertNotEmpty($payload['timestamp']);
        // ISO 8601 format check
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $payload['timestamp']);
    }

    public function test_generate_test_payload_for_transfer_initiated(): void
    {
        $payload = $this->service->generateTestPayload('transfer.initiated');

        $this->assertEquals('transfer.initiated', $payload['event']);
        $this->assertArrayHasKey('transfer_id', $payload['data']);
        $this->assertEquals('500.00', $payload['data']['amount']);
        $this->assertEquals('EUR', $payload['data']['currency']);
        $this->assertEquals('SEPA', $payload['data']['rail']);
    }

    public function test_generate_test_payload_for_account_created(): void
    {
        $payload = $this->service->generateTestPayload('account.created');

        $this->assertEquals('account.created', $payload['event']);
        $this->assertArrayHasKey('account_id', $payload['data']);
        $this->assertEquals('checking', $payload['data']['type']);
    }

    public function test_generate_test_payload_for_consent_authorized(): void
    {
        $payload = $this->service->generateTestPayload('consent.authorized');

        $this->assertEquals('consent.authorized', $payload['event']);
        $this->assertArrayHasKey('consent_id', $payload['data']);
        $this->assertEquals('TPP-001', $payload['data']['tpp_id']);
        $this->assertEquals(['ReadBalances'], $payload['data']['permissions']);
    }

    public function test_generate_test_payload_for_card_authorization(): void
    {
        $payload = $this->service->generateTestPayload('card.authorization');

        $this->assertEquals('card.authorization', $payload['event']);
        $this->assertArrayHasKey('card_id', $payload['data']);
        $this->assertEquals('25.00', $payload['data']['amount']);
        $this->assertEquals('Test Store', $payload['data']['merchant']);
    }

    public function test_generate_test_payload_handles_unknown_event_types_gracefully(): void
    {
        $payload = $this->service->generateTestPayload('unknown.custom.event');

        $this->assertArrayHasKey('event', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('webhook_id', $payload);
        $this->assertEquals('unknown.custom.event', $payload['event']);
        $this->assertEquals('Test event', $payload['data']['message']);
    }

    public function test_generate_test_payload_produces_unique_ids_on_each_call(): void
    {
        $payload1 = $this->service->generateTestPayload('payment.completed');
        $payload2 = $this->service->generateTestPayload('payment.completed');

        $this->assertNotEquals($payload1['webhook_id'], $payload2['webhook_id']);
        $this->assertNotEquals($payload1['data']['payment_id'], $payload2['data']['payment_id']);
    }
}
