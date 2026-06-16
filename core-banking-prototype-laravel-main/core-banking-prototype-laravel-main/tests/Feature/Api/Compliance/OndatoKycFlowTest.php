<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Compliance;

use App\Domain\Compliance\Models\KycVerification;
use App\Domain\Compliance\Services\OndatoService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\ControllerTestCase;

class OndatoKycFlowTest extends ControllerTestCase
{
    protected User $user;

    /** @var OndatoService&MockInterface */
    protected MockInterface $ondatoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'kyc_status' => 'not_started',
            'kyc_level'  => 'basic',
        ]);

        /** @var OndatoService&MockInterface $ondatoService */
        $ondatoService = Mockery::mock(OndatoService::class);
        $this->ondatoService = $ondatoService;
        $this->app->instance(OndatoService::class, $this->ondatoService);

        // Set up a default TrustCert application in cache
        Cache::put("trustcert_application:{$this->user->id}", [
            'id'     => 'app_test_123',
            'status' => 'pending',
            'level'  => 2,
        ], now()->addDays(30));
    }

    #[Test]
    public function test_start_ondato_verification_creates_session(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $this->ondatoService->shouldReceive('createIdentityVerification')
            ->once()
            ->with(
                Mockery::on(fn ($user) => $user->id === $this->user->id),
                Mockery::on(
                    fn ($data) => $data['first_name'] === 'John'
                    && $data['last_name'] === 'Doe'
                    && $data['application_id'] === 'app_test_123'
                    && $data['target_level'] === 'verified'
                )
            )
            ->andReturn([
                'identity_verification_id' => 'idv-new-123',
                'verification_id'          => 'ver-uuid-456',
                'status'                   => 'pending',
            ]);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_test_123',
            'target_level'   => 2,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'identity_verification_id' => 'idv-new-123',
                    'verification_id'          => 'ver-uuid-456',
                    'status'                   => 'pending',
                ],
            ]);
    }

    #[Test]
    public function test_start_ondato_verification_rejects_if_already_approved(): void
    {
        $this->user->update(['kyc_status' => 'approved']);
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_test_123',
            'target_level'   => 2,
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'KYC already approved']);
    }

    #[Test]
    public function test_start_ondato_verification_requires_authentication(): void
    {
        $response = $this->postJson('/api/compliance/kyc/ondato/start');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_start_ondato_verification_requires_application_id_and_target_level(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['application_id', 'target_level']);
    }

    #[Test]
    public function test_start_ondato_verification_validates_target_level_range(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_test_123',
            'target_level'   => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_level']);
    }

    #[Test]
    public function test_start_ondato_verification_rejects_invalid_application_id(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'non_existent_app',
            'target_level'   => 2,
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'TrustCert application not found']);
    }

    #[Test]
    public function test_start_ondato_verification_rejects_completed_application(): void
    {
        Cache::put("trustcert_application:{$this->user->id}", [
            'id'     => 'app_done',
            'status' => 'approved',
        ], now()->addDays(30));

        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_done',
            'target_level'   => 2,
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'TrustCert application is already approved']);
    }

    #[Test]
    public function test_start_ondato_verification_maps_target_levels(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $expectedLevel = 'basic';
        $this->ondatoService->shouldReceive('createIdentityVerification')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(fn ($data) => $data['target_level'] === $expectedLevel)
            )
            ->andReturn([
                'identity_verification_id' => 'idv-level-test',
                'verification_id'          => 'ver-level-test',
                'status'                   => 'pending',
            ]);

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_test_123',
            'target_level'   => 1,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_start_ondato_verification_returns_500_on_service_failure(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $this->ondatoService->shouldReceive('createIdentityVerification')
            ->once()
            ->andThrow(new RuntimeException('API unavailable'));

        $response = $this->postJson('/api/compliance/kyc/ondato/start', [
            'application_id' => 'app_test_123',
            'target_level'   => 2,
        ]);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Failed to start Ondato verification']);
    }

    #[Test]
    public function test_get_ondato_verification_status_with_data_envelope(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $verification = KycVerification::create([
            'user_id'            => $this->user->id,
            'type'               => KycVerification::TYPE_IDENTITY,
            'status'             => KycVerification::STATUS_COMPLETED,
            'provider'           => 'ondato',
            'provider_reference' => 'idv-status-check',
            'target_level'       => 'verified',
            'confidence_score'   => 95.00,
            'completed_at'       => now(),
            'verification_data'  => [],
        ]);

        $response = $this->getJson("/api/compliance/kyc/ondato/status/{$verification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'verification_id'  => $verification->id,
                    'status'           => 'completed',
                    'trust_cert_level' => 2,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'verification_id',
                    'status',
                    'completed_at',
                    'failure_reason',
                    'trust_cert_level',
                ],
            ]);
    }

    #[Test]
    public function test_get_ondato_verification_status_returns_null_trust_cert_level_when_pending(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $verification = KycVerification::create([
            'user_id'            => $this->user->id,
            'type'               => KycVerification::TYPE_IDENTITY,
            'status'             => KycVerification::STATUS_PENDING,
            'provider'           => 'ondato',
            'provider_reference' => 'idv-pending-check',
            'target_level'       => 'verified',
            'verification_data'  => [],
        ]);

        $response = $this->getJson("/api/compliance/kyc/ondato/status/{$verification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'trust_cert_level' => null,
                ],
            ]);
    }

    #[Test]
    public function test_get_ondato_verification_status_returns_404_for_other_user(): void
    {
        $otherUser = User::factory()->create();
        $verification = KycVerification::create([
            'user_id'            => $otherUser->id,
            'type'               => KycVerification::TYPE_IDENTITY,
            'status'             => KycVerification::STATUS_PENDING,
            'provider'           => 'ondato',
            'provider_reference' => 'idv-other-user',
            'verification_data'  => [],
        ]);

        Sanctum::actingAs($this->user, ['read', 'write', 'delete']);

        $response = $this->getJson("/api/compliance/kyc/ondato/status/{$verification->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function test_get_ondato_verification_status_requires_authentication(): void
    {
        $response = $this->getJson('/api/compliance/kyc/ondato/status/some-id');

        $response->assertStatus(401);
    }
}
