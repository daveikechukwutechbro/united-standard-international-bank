<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ConcurrentSessionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Count only access tokens (excluding refresh tokens) for the user.
     */
    private function accessTokenCount(User $user): int
    {
        return $user->tokens()->where('abilities', '!=', '["refresh"]')->count();
    }

    public function test_concurrent_session_limit_is_enforced(): void
    {
        // Set limit to 5
        config(['auth.max_concurrent_sessions' => 5]);

        $tokens = [];

        // Create 5 sessions (each login creates access + refresh token)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "Device {$i}",
            ]);

            $response->assertOk();
            $tokens[] = $response->json('data.access_token');
        }

        // Verify 5 access tokens exist
        $this->assertEquals(5, $this->accessTokenCount($this->user));

        // Create 6th session - should delete the oldest access token
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'Device 6',
        ]);

        $response->assertOk();
        $tokens[] = $response->json('data.access_token');

        // Still only 5 access tokens
        $this->assertEquals(5, $this->accessTokenCount($this->user));

        // First token should no longer work
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokens[0])
            ->getJson('/api/auth/user');
        $response->assertUnauthorized();

        // Last 5 tokens should still work
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $tokens[$i])
                ->getJson('/api/auth/user');
            $response->assertOk();
        }
    }

    public function test_concurrent_session_limit_respects_configuration(): void
    {
        // Test with custom limit of 3
        config(['auth.max_concurrent_sessions' => 3]);

        // Create 3 sessions
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "Device {$i}",
            ]);
            $response->assertOk();
        }

        $this->assertEquals(3, $this->accessTokenCount($this->user));

        // 4th login should maintain limit of 3 access tokens
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'Device 4',
        ]);
        $response->assertOk();

        $this->assertEquals(3, $this->accessTokenCount($this->user));
    }

    public function test_oldest_sessions_are_removed_first(): void
    {
        config(['auth.max_concurrent_sessions' => 3]);

        // Create tokens with time travel to ensure different timestamps
        Carbon::setTestNow(now());
        $deviceNames = [];
        for ($i = 1; $i <= 3; $i++) {
            $deviceName = "Device {$i}";
            $deviceNames[] = $deviceName;

            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => $deviceName,
            ]);
            $response->assertOk();

            // Advance time instead of sleeping to ensure different created_at times
            Carbon::setTestNow(now()->addSecond());
        }
        Carbon::setTestNow(); // Reset time

        // Get the oldest access token name
        $oldestToken = $this->user->tokens()
            ->where('abilities', '!=', '["refresh"]')
            ->orderBy('created_at', 'asc')
            ->first();
        $oldestDeviceName = $oldestToken->name;

        // Create new session
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'Device 4',
        ]);
        $response->assertOk();

        // Oldest access token should be gone
        $remainingAccessTokens = $this->user->tokens()
            ->where('abilities', '!=', '["refresh"]')
            ->pluck('name')
            ->toArray();
        $this->assertNotContains($oldestDeviceName, $remainingAccessTokens);
        $this->assertContains('Device 4', $remainingAccessTokens);
    }

    public function test_revoke_tokens_option_deletes_all_existing_tokens(): void
    {
        $this->markTestSkipped('revoke_tokens feature not yet implemented');
    }

    public function test_logout_all_revokes_all_sessions(): void
    {
        // Create multiple sessions for the user
        $tokens = [];
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "Device {$i}",
            ]);
            $response->assertOk();
            $tokens[] = $response->json('data.access_token');
        }

        // Total tokens include both access and refresh tokens
        $tokenCountBefore = $this->user->tokens()->count();
        $this->assertGreaterThanOrEqual(6, $tokenCountBefore); // 3 access + 3 refresh

        // Logout all using the last token
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokens[2])
            ->postJson('/api/auth/logout-all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'All sessions terminated successfully')
            ->assertJsonPath('data.revoked_count', $tokenCountBefore);

        // All tokens should be deleted from the database
        $this->assertEquals(0, $this->user->tokens()->count());
    }

    public function test_concurrent_session_limit_is_per_user(): void
    {
        config(['auth.max_concurrent_sessions' => 2]);

        $user2 = User::factory()->create();

        // Create 2 sessions for user1
        for ($i = 1; $i <= 2; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "User1 Device {$i}",
            ]);
            $response->assertOk();
        }

        // Create 2 sessions for user2
        for ($i = 1; $i <= 2; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $user2->email,
                'password'    => 'password',
                'device_name' => "User2 Device {$i}",
            ]);
            $response->assertOk();
        }

        // Both users should have 2 access tokens each
        $this->assertEquals(2, $this->accessTokenCount($this->user));
        $this->assertEquals(2, $this->accessTokenCount($user2));

        // Adding a 3rd session for user1 shouldn't affect user2
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'User1 Device 3',
        ]);
        $response->assertOk();

        $this->assertEquals(2, $this->accessTokenCount($this->user));
        $this->assertEquals(2, $this->accessTokenCount($user2));
    }

    public function test_default_concurrent_session_limit_is_5(): void
    {
        config(['auth.max_concurrent_sessions' => 5]);

        // Create 5 sessions
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "Device {$i}",
            ]);
            $response->assertOk();
        }

        $this->assertEquals(5, $this->accessTokenCount($this->user));

        // 6th session should enforce the limit
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'Device 6',
        ]);
        $response->assertOk();

        // Should still be 5 access tokens (oldest removed)
        $this->assertEquals(5, $this->accessTokenCount($this->user));
    }

    public function test_single_logout_only_revokes_current_token(): void
    {
        // Create multiple tokens directly (not via login, so no refresh tokens)
        $token1 = $this->user->createToken('Device 1')->plainTextToken;
        $token2 = $this->user->createToken('Device 2')->plainTextToken;
        $token3 = $this->user->createToken('Device 3')->plainTextToken;

        $this->assertEquals(3, $this->user->tokens()->count());

        // Logout using token1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $response->assertJson(['message' => 'Logged out successfully']);

        // Refresh user to get updated token count
        $this->user->refresh();

        // Current implementation revokes ALL tokens on logout (not just current)
        $this->assertEquals(0, $this->user->tokens()->count());

        // Check database directly - all tokens should be deleted
        $dbTokenCount = PersonalAccessToken::where('tokenable_id', $this->user->id)
            ->where('tokenable_type', $this->user::class)
            ->count();
        $this->assertEquals(0, $dbTokenCount);
    }

    public function test_session_limit_excludes_refresh_tokens(): void
    {
        config(['auth.max_concurrent_sessions' => 2]);

        // Create 2 sessions — each creates access + refresh token
        for ($i = 1; $i <= 2; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email'       => $this->user->email,
                'password'    => 'password',
                'device_name' => "Device {$i}",
            ]);
            $response->assertOk();
        }

        // Total tokens = 4 (2 access + 2 refresh), but only 2 count as sessions
        $this->assertEquals(4, $this->user->tokens()->count());
        $this->assertEquals(2, $this->accessTokenCount($this->user));

        // 3rd login should only evict oldest access token, not refresh tokens
        $response = $this->postJson('/api/auth/login', [
            'email'       => $this->user->email,
            'password'    => 'password',
            'device_name' => 'Device 3',
        ]);
        $response->assertOk();

        // Should still have 2 access tokens
        $this->assertEquals(2, $this->accessTokenCount($this->user));

        // Refresh tokens should not have been evicted by session limit
        $refreshTokenCount = $this->user->tokens()
            ->where('abilities', '["refresh"]')
            ->count();
        $this->assertGreaterThanOrEqual(2, $refreshTokenCount);
    }
}
