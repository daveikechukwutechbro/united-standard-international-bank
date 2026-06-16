<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Webhook\Services;

use App\Domain\Webhook\Services\PayloadSanitizer;
use Tests\TestCase;

class PayloadSanitizerTest extends TestCase
{
    private PayloadSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new PayloadSanitizer();
    }

    public function test_non_sensitive_keys_are_passed_through_unchanged(): void
    {
        $payload = [
            'event'      => 'payment.completed',
            'amount'     => 100,
            'currency'   => 'EUR',
            'account_id' => 'acc_123',
        ];

        $result = $this->sanitizer->sanitize($payload);

        $this->assertSame($payload, $result);
    }

    public function test_key_containing_secret_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['api_secret' => 'super-secret-value']);

        $this->assertSame('[REDACTED]', $result['api_secret']);
    }

    public function test_key_containing_key_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['api_key' => 'sk_live_abc123']);

        $this->assertSame('[REDACTED]', $result['api_key']);
    }

    public function test_key_containing_token_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['access_token' => 'bearer xyz']);

        $this->assertSame('[REDACTED]', $result['access_token']);
    }

    public function test_key_containing_password_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['user_password' => 'hunter2']);

        $this->assertSame('[REDACTED]', $result['user_password']);
    }

    public function test_key_containing_ssn_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['ssn_number' => '123-45-6789']);

        $this->assertSame('[REDACTED]', $result['ssn_number']);
    }

    public function test_key_containing_pan_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['card_pan' => '4111111111111111']);

        $this->assertSame('[REDACTED]', $result['card_pan']);
    }

    public function test_key_containing_cvv_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['cvv_code' => '123']);

        $this->assertSame('[REDACTED]', $result['cvv_code']);
    }

    public function test_key_containing_pin_is_redacted(): void
    {
        $result = $this->sanitizer->sanitize(['card_pin' => '1234']);

        $this->assertSame('[REDACTED]', $result['card_pin']);
    }

    public function test_sensitive_key_matching_is_case_insensitive(): void
    {
        $result = $this->sanitizer->sanitize([
            'API_KEY'    => 'value1',
            'Secret'     => 'value2',
            'TOKEN_DATA' => 'value3',
        ]);

        $this->assertSame('[REDACTED]', $result['API_KEY']);
        $this->assertSame('[REDACTED]', $result['Secret']);
        $this->assertSame('[REDACTED]', $result['TOKEN_DATA']);
    }

    public function test_nested_sensitive_keys_are_redacted(): void
    {
        $payload = [
            'event' => 'payment.completed',
            'data'  => [
                'amount'  => 100,
                'api_key' => 'sk_live_nested',
                'user'    => [
                    'name'     => 'Alice',
                    'password' => 'secret123',
                ],
            ],
        ];

        $result = $this->sanitizer->sanitize($payload);

        $this->assertSame('payment.completed', $result['event']);
        $this->assertSame(100, $result['data']['amount']);
        $this->assertSame('[REDACTED]', $result['data']['api_key']);
        $this->assertSame('Alice', $result['data']['user']['name']);
        $this->assertSame('[REDACTED]', $result['data']['user']['password']);
    }

    public function test_array_values_are_not_redacted_only_their_sensitive_keys(): void
    {
        $payload = [
            'metadata' => ['env' => 'prod', 'version' => '1.0'],
        ];

        $result = $this->sanitizer->sanitize($payload);

        $this->assertSame(['env' => 'prod', 'version' => '1.0'], $result['metadata']);
    }

    public function test_non_string_values_under_sensitive_keys_are_redacted(): void
    {
        // Even if the value is not a string, the key match should still redact
        $result = $this->sanitizer->sanitize(['pin' => 9999]);

        $this->assertSame('[REDACTED]', $result['pin']);
    }

    public function test_empty_payload_returns_empty_array(): void
    {
        $result = $this->sanitizer->sanitize([]);

        $this->assertSame([], $result);
    }

    public function test_original_payload_is_not_mutated(): void
    {
        $original = ['api_key' => 'secret', 'amount' => 50];
        $copy = $original;

        $this->sanitizer->sanitize($original);

        // Sanitize should return a new array, not mutate the input
        $this->assertSame($copy, $original);
    }

    public function test_exact_sensitive_pattern_name_as_key_is_redacted(): void
    {
        $patterns = ['secret', 'key', 'token', 'password', 'ssn', 'pan', 'cvv', 'pin'];

        foreach ($patterns as $pattern) {
            $result = $this->sanitizer->sanitize([$pattern => 'sensitive-value']);
            $this->assertSame('[REDACTED]', $result[$pattern], "Key '{$pattern}' should be redacted");
        }
    }
}
