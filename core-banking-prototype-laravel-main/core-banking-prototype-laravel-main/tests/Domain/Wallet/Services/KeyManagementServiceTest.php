<?php

declare(strict_types=1);

namespace Tests\Domain\Wallet\Services;

use App\Domain\Wallet\Services\KeyManagementService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KeyManagementService.
 *
 * Tests cryptographic operations and key management functionality.
 */
class KeyManagementServiceTest extends TestCase
{
    private KeyManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service without Laravel dependencies
        $this->service = new class () extends KeyManagementService {
            public function __construct()
            {
                // Skip parent constructor to avoid config() call
                // EC class is optional
            }
        };
    }

    public function test_generate_mnemonic_returns_12_words_by_default(): void
    {
        $mnemonic = $this->service->generateMnemonicWithWordCount(12);

        $words = explode(' ', $mnemonic);
        $this->assertCount(12, $words);
    }

    public function test_generate_mnemonic_returns_24_words_when_specified(): void
    {
        $mnemonic = $this->service->generateMnemonicWithWordCount(24);

        $words = explode(' ', $mnemonic);
        $this->assertCount(24, $words);
    }

    public function test_generate_mnemonic_returns_random_phrases(): void
    {
        $mnemonic1 = $this->service->generateMnemonicWithWordCount(12);
        $mnemonic2 = $this->service->generateMnemonicWithWordCount(12);

        // Different calls should produce different mnemonics
        // (extremely unlikely to be the same)
        $this->assertNotEquals($mnemonic1, $mnemonic2);
    }

    public function test_validate_mnemonic_accepts_12_words(): void
    {
        $mnemonic = 'abandon ability able about above absent absorb abstract absurd abuse access accident';

        $this->assertTrue($this->service->validateMnemonic($mnemonic));
    }

    public function test_validate_mnemonic_accepts_24_words(): void
    {
        $mnemonic = 'abandon ability able about above absent absorb abstract absurd abuse access accident ' .
                    'account accuse achieve acid acoustic acquire across act action actor actress actual';

        $this->assertTrue($this->service->validateMnemonic($mnemonic));
    }

    public function test_validate_mnemonic_rejects_invalid_word_count(): void
    {
        $mnemonic11Words = 'abandon ability able about above absent absorb abstract absurd abuse access';
        $mnemonic6Words = 'abandon ability able about above absent';

        $this->assertFalse($this->service->validateMnemonic($mnemonic11Words));
        $this->assertFalse($this->service->validateMnemonic($mnemonic6Words));
    }

    public function test_validate_mnemonic_handles_extra_whitespace(): void
    {
        $mnemonic = '  abandon ability able about above absent absorb abstract absurd abuse access accident  ';

        $this->assertTrue($this->service->validateMnemonic($mnemonic));
    }

    public function test_generate_key_returns_64_character_hex(): void
    {
        $key = $this->service->generateKey();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key);
    }

    public function test_generate_key_returns_unique_keys(): void
    {
        $key1 = $this->service->generateKey();
        $key2 = $this->service->generateKey();

        $this->assertNotEquals($key1, $key2);
    }

    public function test_sign_creates_hmac_signature(): void
    {
        $data = 'test data to sign';
        $privateKey = 'test_private_key';

        $signature = $this->service->sign($data, $privateKey);

        // HMAC SHA256 produces 64 character hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function test_sign_produces_consistent_signature(): void
    {
        $data = 'test data to sign';
        $privateKey = 'test_private_key';

        $signature1 = $this->service->sign($data, $privateKey);
        $signature2 = $this->service->sign($data, $privateKey);

        $this->assertEquals($signature1, $signature2);
    }

    public function test_sign_produces_different_signature_for_different_data(): void
    {
        $privateKey = 'test_private_key';

        $signature1 = $this->service->sign('data one', $privateKey);
        $signature2 = $this->service->sign('data two', $privateKey);

        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_sign_produces_different_signature_for_different_keys(): void
    {
        $data = 'test data';

        $signature1 = $this->service->sign($data, 'key_one');
        $signature2 = $this->service->sign($data, 'key_two');

        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $data = 'test data';
        $key = 'shared_secret';

        $signature = $this->service->sign($data, $key);

        $this->assertTrue($this->service->verify($data, $signature, $key));
    }

    public function test_verify_returns_false_for_invalid_signature(): void
    {
        $data = 'test data';
        $key = 'shared_secret';

        $this->assertFalse($this->service->verify($data, 'invalid_signature', $key));
    }

    public function test_verify_returns_false_for_tampered_data(): void
    {
        $originalData = 'original data';
        $tamperedData = 'tampered data';
        $key = 'shared_secret';

        $signature = $this->service->sign($originalData, $key);

        $this->assertFalse($this->service->verify($tamperedData, $signature, $key));
    }

    public function test_verify_returns_false_for_wrong_key(): void
    {
        $data = 'test data';
        $signingKey = 'signing_key';
        $verificationKey = 'different_key';

        $signature = $this->service->sign($data, $signingKey);

        $this->assertFalse($this->service->verify($data, $signature, $verificationKey));
    }

    public function test_generate_mnemonic_interface_method_works(): void
    {
        $mnemonic = $this->service->generateMnemonic();

        $words = explode(' ', $mnemonic);
        $this->assertCount(12, $words);
    }

    public function test_generate_mnemonic_accepts_word_count(): void
    {
        $mnemonic = $this->service->generateMnemonic(24);

        $words = explode(' ', $mnemonic);
        $this->assertCount(24, $words);
    }

    public function test_generated_mnemonic_is_valid(): void
    {
        $mnemonic = $this->service->generateMnemonicWithWordCount(12);

        $this->assertTrue($this->service->validateMnemonic($mnemonic));
    }

    public function test_generate_deterministic_key_returns_consistent_key(): void
    {
        $seed = 'test_seed_value';

        $key1 = $this->service->generateDeterministicKey($seed);
        $key2 = $this->service->generateDeterministicKey($seed);

        $this->assertEquals($key1, $key2);
    }

    public function test_generate_deterministic_key_different_for_different_seed(): void
    {
        $key1 = $this->service->generateDeterministicKey('seed_one');
        $key2 = $this->service->generateDeterministicKey('seed_two');

        $this->assertNotEquals($key1, $key2);
    }

    public function test_generate_deterministic_key_returns_sha256_hash(): void
    {
        $seed = 'test_seed';
        $key = $this->service->generateDeterministicKey($seed);

        // SHA256 produces 64 character hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key);
    }
}
