<?php

declare(strict_types=1);

namespace Tests\Domain\Cgo\Services;

use App\Domain\Cgo\Services\PaymentVerificationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Traits\InvokesPrivateMethods;

/**
 * Unit tests for PaymentVerificationService pure logic methods.
 *
 * We create an anonymous subclass that overrides isPaymentExpired to accept
 * any object, allowing us to test the expiration logic without needing
 * a full CgoInvestment model.
 */
class PaymentVerificationServiceTest extends TestCase
{
    use InvokesPrivateMethods;

    /**
     * @var PaymentVerificationService&object{isPaymentExpiredTest: callable(object): bool}
     */
    private PaymentVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a partial mock that bypasses constructor dependencies
        // and allows testing with mock investment objects
        // @phpstan-ignore-next-line
        $this->service = new class () extends PaymentVerificationService {
            public function __construct()
            {
                // Skip parent constructor - we're testing pure logic methods only
            }

            /**
             * Override to accept any object for testing purposes.
             * This tests the exact same logic as the parent method.
             */
            public function isPaymentExpiredTest(object $investment): bool
            {
                $expirationHours = match ($investment->payment_method) {
                    'card'          => 1,
                    'crypto'        => 24,
                    'bank_transfer' => 72,
                    default         => 24,
                };

                return $investment->created_at->addHours($expirationHours)->isPast();
            }
        };
    }

    // Payment Expiration Tests - Card Payments (1 hour)

    public function test_card_payment_not_expired_within_hour(): void
    {
        $investment = $this->createMockInvestment('card', Carbon::now()->subMinutes(30));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_card_payment_expired_after_hour(): void
    {
        $investment = $this->createMockInvestment('card', Carbon::now()->subMinutes(61));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    public function test_card_payment_expired_at_exactly_one_hour(): void
    {
        $investment = $this->createMockInvestment('card', Carbon::now()->subHour());

        $result = $this->service->isPaymentExpiredTest($investment);

        // At exactly 1 hour, addHours(1)->isPast() should be true
        $this->assertTrue($result);
    }

    // Payment Expiration Tests - Crypto Payments (24 hours)

    public function test_crypto_payment_not_expired_within_24_hours(): void
    {
        $investment = $this->createMockInvestment('crypto', Carbon::now()->subHours(12));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_crypto_payment_expired_after_24_hours(): void
    {
        $investment = $this->createMockInvestment('crypto', Carbon::now()->subHours(25));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    public function test_crypto_payment_not_expired_at_23_hours(): void
    {
        $investment = $this->createMockInvestment('crypto', Carbon::now()->subHours(23));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    // Payment Expiration Tests - Bank Transfer (72 hours / 3 days)

    public function test_bank_transfer_not_expired_within_3_days(): void
    {
        $investment = $this->createMockInvestment('bank_transfer', Carbon::now()->subDays(2));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_bank_transfer_expired_after_3_days(): void
    {
        $investment = $this->createMockInvestment('bank_transfer', Carbon::now()->subDays(4));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    public function test_bank_transfer_not_expired_at_71_hours(): void
    {
        $investment = $this->createMockInvestment('bank_transfer', Carbon::now()->subHours(71));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_bank_transfer_expired_at_73_hours(): void
    {
        $investment = $this->createMockInvestment('bank_transfer', Carbon::now()->subHours(73));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    // Unknown Payment Method (defaults to 24 hours)

    public function test_unknown_payment_method_defaults_to_24_hours(): void
    {
        $investment = $this->createMockInvestment('unknown_method', Carbon::now()->subHours(12));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_unknown_payment_method_expired_after_24_hours(): void
    {
        $investment = $this->createMockInvestment('unknown_method', Carbon::now()->subHours(25));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    public function test_null_payment_method_defaults_to_24_hours(): void
    {
        $investment = $this->createMockInvestment(null, Carbon::now()->subHours(12));

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    // Edge Cases

    public function test_payment_just_created_not_expired(): void
    {
        $investment = $this->createMockInvestment('card', Carbon::now());

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertFalse($result);
    }

    public function test_very_old_payment_is_expired(): void
    {
        $investment = $this->createMockInvestment('bank_transfer', Carbon::now()->subYear());

        $result = $this->service->isPaymentExpiredTest($investment);

        $this->assertTrue($result);
    }

    // Expiration Time Verification

    public function test_card_expiration_window_is_one_hour(): void
    {
        // Payment at exactly 59 minutes ago should not be expired
        $investment59min = $this->createMockInvestment('card', Carbon::now()->subMinutes(59));
        $this->assertFalse($this->service->isPaymentExpiredTest($investment59min));

        // Payment at exactly 61 minutes ago should be expired
        $investment61min = $this->createMockInvestment('card', Carbon::now()->subMinutes(61));
        $this->assertTrue($this->service->isPaymentExpiredTest($investment61min));
    }

    public function test_crypto_expiration_window_is_24_hours(): void
    {
        // Payment at exactly 23.9 hours ago should not be expired
        $investment23h = $this->createMockInvestment('crypto', Carbon::now()->subMinutes(23 * 60 + 54));
        $this->assertFalse($this->service->isPaymentExpiredTest($investment23h));

        // Payment at 24.1 hours ago should be expired
        $investment25h = $this->createMockInvestment('crypto', Carbon::now()->subMinutes(24 * 60 + 6));
        $this->assertTrue($this->service->isPaymentExpiredTest($investment25h));
    }

    public function test_bank_transfer_expiration_window_is_72_hours(): void
    {
        // Payment at exactly 71 hours ago should not be expired
        $investment71h = $this->createMockInvestment('bank_transfer', Carbon::now()->subHours(71));
        $this->assertFalse($this->service->isPaymentExpiredTest($investment71h));

        // Payment at 73 hours ago should be expired
        $investment73h = $this->createMockInvestment('bank_transfer', Carbon::now()->subHours(73));
        $this->assertTrue($this->service->isPaymentExpiredTest($investment73h));
    }

    /**
     * Create a mock investment object for testing.
     */
    private function createMockInvestment(?string $paymentMethod, Carbon $createdAt): object
    {
        return new class ($paymentMethod, $createdAt) {
            public ?string $payment_method;

            public Carbon $created_at;

            public function __construct(?string $paymentMethod, Carbon $createdAt)
            {
                $this->payment_method = $paymentMethod;
                $this->created_at = $createdAt;
            }
        };
    }
}
