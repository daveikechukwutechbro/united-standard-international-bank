<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Compliance;

use App\Domain\Compliance\Services\OndatoService;
use App\Jobs\ProcessOndatoWebhook;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class OndatoWebhookTest extends ControllerTestCase
{
    /** @var OndatoService&MockInterface */
    protected MockInterface $ondatoService;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var OndatoService&MockInterface $ondatoService */
        $ondatoService = Mockery::mock(OndatoService::class);
        $this->ondatoService = $ondatoService;
        $this->app->instance(OndatoService::class, $this->ondatoService);
    }

    #[Test]
    public function test_identity_verification_webhook_returns_200_and_dispatches_job(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/api/webhooks/ondato/identity-verification', [
            'id'                     => 'idv-webhook-123',
            'identityVerificationId' => 'idv-456',
            'status'                 => 'PROCESSED',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'received']);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->eventType === 'PROCESSED'
                && $job->webhookType === 'identity-verification';
        });
    }

    #[Test]
    public function test_identification_webhook_returns_200_and_dispatches_job(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/api/webhooks/ondato/identification', [
            'id'                     => 'ident-webhook-789',
            'identityVerificationId' => 'idv-999',
            'status'                 => 'REJECTED',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'received']);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->eventType === 'REJECTED'
                && $job->webhookType === 'identification';
        });
    }

    #[Test]
    public function test_webhook_rejects_invalid_signature(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(false);

        $response = $this->postJson('/api/webhooks/ondato/identity-verification', [
            'id'     => 'test-id',
            'status' => 'PROCESSED',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_webhook_rejects_invalid_json(): void
    {
        Queue::fake();

        $response = $this->call(
            'POST',
            '/api/webhooks/ondato/identity-verification',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-valid-json'
        );

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid payload']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_webhook_extracts_event_type_from_status_field(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/api/webhooks/ondato/identity-verification', [
            'id'     => 'type-test-123',
            'status' => 'EXPIRED',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->eventType === 'EXPIRED';
        });
    }

    #[Test]
    public function test_webhook_extracts_event_type_from_type_field_as_fallback(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/api/webhooks/ondato/identification', [
            'id'   => 'type-fallback-123',
            'type' => 'CONSENT_AGREEMENT_ACCEPTED',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->eventType === 'CONSENT_AGREEMENT_ACCEPTED';
        });
    }

    #[Test]
    public function test_webhook_defaults_to_unknown_event_type(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/api/webhooks/ondato/identity-verification', [
            'id'        => 'no-type-123',
            'someField' => 'value',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->eventType === 'UNKNOWN';
        });
    }

    #[Test]
    public function test_webhook_does_not_require_authentication(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        // Webhook endpoints should work without Sanctum auth
        $response = $this->postJson('/api/webhooks/ondato/identity-verification', [
            'id'     => 'no-auth-test',
            'status' => 'STARTED',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_webhook_passes_full_payload_to_job(): void
    {
        Queue::fake();

        $this->ondatoService->shouldReceive('validateWebhookSignature')
            ->once()
            ->andReturn(true);

        $payload = [
            'id'                     => 'full-payload-123',
            'identityVerificationId' => 'idv-full-456',
            'status'                 => 'PROCESSED',
            'document'               => ['type' => 'Passport', 'number' => 'P123'],
            'person'                 => ['firstName' => 'Test', 'lastName' => 'User'],
        ];

        $this->postJson('/api/webhooks/ondato/identity-verification', $payload);

        Queue::assertPushed(ProcessOndatoWebhook::class, function ($job) {
            return $job->payload['identityVerificationId'] === 'idv-full-456'
                && $job->payload['document']['type'] === 'Passport';
        });
    }
}
