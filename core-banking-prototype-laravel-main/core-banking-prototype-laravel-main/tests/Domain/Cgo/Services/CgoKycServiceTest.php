<?php

declare(strict_types=1);

namespace Tests\Domain\Cgo\Services;

use App\Domain\Cgo\Services\CgoKycService;
use PHPUnit\Framework\TestCase;
use Tests\Traits\InvokesPrivateMethods;

/**
 * Unit tests for CgoKycService pure logic methods.
 */
class CgoKycServiceTest extends TestCase
{
    use InvokesPrivateMethods;

    private CgoKycService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a partial mock that bypasses constructor dependencies
        // @phpstan-ignore-next-line
        $this->service = new class () extends CgoKycService {
            public function __construct()
            {
                // Skip parent constructor - we're testing pure logic methods only
            }
        };
    }

    // KYC Level Determination Tests

    public function test_determine_required_kyc_level_basic_for_small_amounts(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [500.00]);

        $this->assertEquals('basic', $level);
    }

    public function test_determine_required_kyc_level_basic_at_threshold(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [1000.00]);

        $this->assertEquals('basic', $level);
    }

    public function test_determine_required_kyc_level_enhanced_above_basic_threshold(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [1001.00]);

        $this->assertEquals('enhanced', $level);
    }

    public function test_determine_required_kyc_level_enhanced_at_threshold(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [10000.00]);

        $this->assertEquals('enhanced', $level);
    }

    public function test_determine_required_kyc_level_full_above_enhanced_threshold(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [10001.00]);

        $this->assertEquals('full', $level);
    }

    public function test_determine_required_kyc_level_full_for_large_amounts(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [100000.00]);

        $this->assertEquals('full', $level);
    }

    // KYC Sufficiency Tests

    public function test_kyc_sufficient_with_approved_status_and_matching_level(): void
    {
        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'enhanced',
            'expires_at' => null,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'enhanced']);

        $this->assertTrue($result);
    }

    public function test_kyc_sufficient_with_higher_level_than_required(): void
    {
        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'full',
            'expires_at' => null,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'basic']);

        $this->assertTrue($result);
    }

    public function test_kyc_insufficient_with_pending_status(): void
    {
        $currentStatus = [
            'status'     => 'pending',
            'level'      => 'enhanced',
            'expires_at' => null,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'enhanced']);

        $this->assertFalse($result);
    }

    public function test_kyc_insufficient_with_lower_level_than_required(): void
    {
        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'basic',
            'expires_at' => null,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'enhanced']);

        $this->assertFalse($result);
    }

    public function test_kyc_insufficient_with_expired_kyc(): void
    {
        // Create a Carbon instance for past date
        $expiredDate = new class () {
            public function isPast(): bool
            {
                return true;
            }
        };

        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'full',
            'expires_at' => $expiredDate,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'basic']);

        $this->assertFalse($result);
    }

    public function test_kyc_sufficient_with_future_expiration(): void
    {
        // Create a Carbon instance for future date
        $futureDate = new class () {
            public function isPast(): bool
            {
                return false;
            }
        };

        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'enhanced',
            'expires_at' => $futureDate,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'basic']);

        $this->assertTrue($result);
    }

    public function test_kyc_insufficient_with_unknown_level(): void
    {
        $currentStatus = [
            'status'     => 'approved',
            'level'      => 'unknown_level',
            'expires_at' => null,
        ];

        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'basic']);

        $this->assertFalse($result);
    }

    // Additional Checks Tests

    public function test_additional_checks_for_basic_level(): void
    {
        $checks = $this->invokeMethod($this->service, 'getAdditionalChecks', ['basic']);

        $this->assertIsArray($checks);
        $this->assertEmpty($checks);
    }

    public function test_additional_checks_for_enhanced_level(): void
    {
        $checks = $this->invokeMethod($this->service, 'getAdditionalChecks', ['enhanced']);

        $this->assertContains('pep_screening', $checks);
        $this->assertContains('sanctions_screening', $checks);
        $this->assertContains('adverse_media_check', $checks);
        $this->assertCount(3, $checks);
    }

    public function test_additional_checks_for_full_level(): void
    {
        $checks = $this->invokeMethod($this->service, 'getAdditionalChecks', ['full']);

        $this->assertContains('pep_screening', $checks);
        $this->assertContains('sanctions_screening', $checks);
        $this->assertContains('adverse_media_check', $checks);
        $this->assertContains('source_of_wealth', $checks);
        $this->assertContains('source_of_funds', $checks);
        $this->assertContains('financial_profile', $checks);
        $this->assertCount(6, $checks);
    }

    // Threshold Constants Tests

    public function test_basic_kyc_threshold_constant(): void
    {
        $this->assertEquals(1000, CgoKycService::BASIC_KYC_THRESHOLD);
    }

    public function test_enhanced_kyc_threshold_constant(): void
    {
        $this->assertEquals(10000, CgoKycService::ENHANCED_KYC_THRESHOLD);
    }

    public function test_full_kyc_threshold_constant(): void
    {
        $this->assertEquals(50000, CgoKycService::FULL_KYC_THRESHOLD);
    }

    // Edge Cases

    public function test_determine_required_kyc_level_zero_amount(): void
    {
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [0.00]);

        $this->assertEquals('basic', $level);
    }

    public function test_determine_required_kyc_level_negative_amount_returns_basic(): void
    {
        // Edge case: negative amounts should still return basic
        $level = $this->invokeMethod($this->service, 'determineRequiredKycLevel', [-100.00]);

        $this->assertEquals('basic', $level);
    }

    public function test_kyc_level_hierarchy_basic_to_enhanced(): void
    {
        // basic (1) < enhanced (2)
        $currentStatus = ['status' => 'approved', 'level' => 'basic', 'expires_at' => null];
        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'enhanced']);

        $this->assertFalse($result);
    }

    public function test_kyc_level_hierarchy_enhanced_to_full(): void
    {
        // enhanced (2) < full (3)
        $currentStatus = ['status' => 'approved', 'level' => 'enhanced', 'expires_at' => null];
        $result = $this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'full']);

        $this->assertFalse($result);
    }

    public function test_kyc_level_hierarchy_full_covers_all(): void
    {
        // full (3) >= all levels
        $currentStatus = ['status' => 'approved', 'level' => 'full', 'expires_at' => null];

        $this->assertTrue($this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'basic']));
        $this->assertTrue($this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'enhanced']));
        $this->assertTrue($this->invokeMethod($this->service, 'isKycSufficient', [$currentStatus, 'full']));
    }
}
