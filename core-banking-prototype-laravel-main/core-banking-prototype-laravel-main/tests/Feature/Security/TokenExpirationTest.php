<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class TokenExpirationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->setUpSecurityTesting(); // Removed - trait deleted
        $this->user = User::factory()->create();
    }

    public function test_token_has_expiration_set_when_created(): void
    {
        // Set expiration to 60 minutes
        config(['sanctum.expiration' => 60]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'access_token',
                'refresh_token',
                'expires_in',
                'refresh_expires_in',
            ],
        ]);

        // Check that expires_in is set correctly (60 minutes = 3600 seconds)
        $this->assertEquals(3600, $response->json('data.expires_in'));

        // Check database for access token expiration
        $token = $this->user->tokens()->where('abilities', '!=', '["refresh"]')->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);

        // Token should expire in approximately 60 minutes
        $diffInMinutes = Carbon::now()->diffInMinutes($token->expires_at);
        $this->assertGreaterThanOrEqual(59, $diffInMinutes);
        $this->assertLessThanOrEqual(61, $diffInMinutes);
    }

    public function test_expired_token_is_rejected(): void
    {
        // Create a token
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Manually expire the token
        $this->user->tokens()->first()->update([
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        // Try to use the expired token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/user');

        // Sanctum returns Unauthenticated for expired tokens
        $response->assertUnauthorized();
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_valid_token_passes_expiration_check(): void
    {
        // Create a token with future expiration
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Set expiration to future
        $this->user->tokens()->first()->update([
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // Use the valid token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/user');

        $response->assertOk();
        $response->assertJsonFragment([
            'id'    => $this->user->id,
            'email' => $this->user->email,
        ]);
    }

    public function test_token_without_expiration_is_accepted(): void
    {
        // Create a token without expiration
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Remove expiration
        $this->user->tokens()->first()->update([
            'expires_at' => null,
        ]);

        // Use the token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/user');

        $response->assertOk();
    }

    public function test_token_refresh_maintains_scopes(): void
    {
        config(['sanctum.expiration' => 60]);
        config(['sanctum.refresh_token_expiration' => 43200]);

        // Login to get a token pair
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $refreshToken = $loginResponse->json('data.refresh_token');
        $this->assertNotEmpty($refreshToken);

        // Refresh using the refresh token (sent in body, no Bearer auth needed)
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'refresh_expires_in',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.expires_in', 3600);

        $newAccessToken = $refreshResponse->json('data.access_token');
        $newRefreshToken = $refreshResponse->json('data.refresh_token');
        $this->assertNotEmpty($newAccessToken);
        $this->assertNotEmpty($newRefreshToken);
        $this->assertNotEquals($refreshToken, $newRefreshToken);

        // New access token should work
        $newTokenResponse = $this->withHeader('Authorization', 'Bearer ' . $newAccessToken)
            ->getJson('/api/auth/user');
        $newTokenResponse->assertOk();

        // New access token should have expiration set
        $newTokenRecord = $this->user->tokens()
            ->where('abilities', '!=', '["refresh"]')
            ->latest('id')
            ->first();
        $this->assertNotNull($newTokenRecord->expires_at);
    }

    public function test_refresh_works_after_access_token_expires(): void
    {
        config(['sanctum.expiration' => 60]);
        config(['sanctum.refresh_token_expiration' => 43200]);

        // Login to get a token pair
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $accessToken = $loginResponse->json('data.access_token');
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Expire the access token
        $this->user->tokens()
            ->where('abilities', '!=', '["refresh"]')
            ->update(['expires_at' => Carbon::now()->subMinute()]);

        // Verify access token is rejected
        $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/auth/user')
            ->assertUnauthorized();

        // Refresh should still work with the refresh token
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertOk();
        $newAccessToken = $refreshResponse->json('data.access_token');

        // New access token should work
        $this->withHeader('Authorization', 'Bearer ' . $newAccessToken)
            ->getJson('/api/auth/user')
            ->assertOk();
    }

    public function test_refresh_rejects_access_tokens(): void
    {
        config(['sanctum.expiration' => 60]);
        config(['sanctum.refresh_token_expiration' => 43200]);

        // Login to get a token pair
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $accessToken = $loginResponse->json('data.access_token');

        // Try to refresh using the access token (should fail — wrong ability)
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $accessToken,
        ]);

        $refreshResponse->assertUnauthorized();
    }

    public function test_refresh_rejects_expired_refresh_tokens(): void
    {
        config(['sanctum.expiration' => 60]);
        config(['sanctum.refresh_token_expiration' => 43200]);

        // Login to get a token pair
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Expire the refresh token
        $this->user->tokens()
            ->where('abilities', '["refresh"]')
            ->update(['expires_at' => Carbon::now()->subMinute()]);

        // Refresh should fail
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertUnauthorized();
    }

    public function test_refresh_rotates_tokens_old_revoked(): void
    {
        config(['sanctum.expiration' => 60]);
        config(['sanctum.refresh_token_expiration' => 43200]);

        // Login to get a token pair
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertOk();
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Refresh to get new pair
        $refreshResponse = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertOk();

        // Old refresh token should be revoked (trying again should fail)
        $secondRefresh = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $secondRefresh->assertUnauthorized();
    }

    public function test_refresh_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertUnauthorized();
    }

    public function test_multiple_expired_tokens_are_handled_correctly(): void
    {
        // Create multiple tokens
        $token1 = $this->user->createToken('token1')->plainTextToken;
        $token2 = $this->user->createToken('token2')->plainTextToken;
        $token3 = $this->user->createToken('token3')->plainTextToken;

        // Expire first two tokens
        $this->user->tokens()->whereIn('name', ['token1', 'token2'])->update([
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        // Try to use expired token1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/auth/user');
        $response->assertUnauthorized();

        // Try to use expired token2
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/auth/user');
        $response->assertUnauthorized();

        // Valid token3 should still work
        $response = $this->withHeader('Authorization', 'Bearer ' . $token3)
            ->getJson('/api/auth/user');
        $response->assertOk();

        // All 3 tokens still exist (expired tokens are not automatically deleted by Sanctum)
        $this->assertEquals(3, $this->user->tokens()->count());
    }

    public function test_token_expiration_respects_sanctum_config(): void
    {
        // Test with no expiration set
        config(['sanctum.expiration' => null]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertNull($response->json('data.expires_in'));

        // Access token should not have expires_at set
        $token = $this->user->tokens()->where('abilities', '!=', '["refresh"]')->first();
        $this->assertNull($token->expires_at);
    }

    public function test_token_expiration_is_set_correctly_with_config(): void
    {
        // Test with 120 minutes expiration
        config(['sanctum.expiration' => 120]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertEquals(7200, $response->json('data.expires_in')); // 120 minutes = 7200 seconds

        // Check database for access token
        $token = $this->user->tokens()->where('abilities', '!=', '["refresh"]')->first();
        $this->assertNotNull($token->expires_at);

        // Should expire in approximately 120 minutes
        $diffInMinutes = Carbon::now()->diffInMinutes($token->expires_at);
        $this->assertGreaterThanOrEqual(119, $diffInMinutes);
        $this->assertLessThanOrEqual(121, $diffInMinutes);
    }
}
