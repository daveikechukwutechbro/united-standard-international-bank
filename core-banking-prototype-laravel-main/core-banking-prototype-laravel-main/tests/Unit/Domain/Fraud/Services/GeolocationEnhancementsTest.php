<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fraud\Services;

use App\Domain\Fraud\Services\DeviceFingerprintService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeolocationEnhancementsTest extends TestCase
{
    #[Test]
    public function ip_reputation_flags_vpn(): void
    {
        Cache::flush();
        $service = $this->createPartialMock(DeviceFingerprintService::class, ['getIpData', 'countBlockedTransactionsForIp']);
        $service->method('getIpData')->willReturn([
            'country'    => 'US',
            'is_vpn'     => true,
            'is_proxy'   => false,
            'is_tor'     => false,
            'risk_score' => 20,
        ]);
        $service->method('countBlockedTransactionsForIp')->willReturn(0);

        $result = $service->assessIpReputation('1.2.3.4');

        $this->assertContains('vpn_detected', $result['flags']);
        $this->assertGreaterThan(0, $result['risk_score']);
    }

    #[Test]
    public function ip_reputation_flags_tor_with_high_score(): void
    {
        Cache::flush();
        $service = $this->createPartialMock(DeviceFingerprintService::class, ['getIpData', 'countBlockedTransactionsForIp']);
        $service->method('getIpData')->willReturn([
            'country'    => 'RU',
            'is_vpn'     => false,
            'is_proxy'   => true,
            'is_tor'     => true,
            'risk_score' => 80,
        ]);
        $service->method('countBlockedTransactionsForIp')->willReturn(5);

        $result = $service->assessIpReputation('10.0.0.1');

        $this->assertContains('tor_detected', $result['flags']);
        $this->assertContains('proxy_detected', $result['flags']);
        $this->assertContains('high_provider_risk', $result['flags']);
        $this->assertContains('associated_with_blocked_transactions', $result['flags']);
        // Score capped at 100
        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }

    #[Test]
    public function ip_reputation_returns_zero_when_ip_data_unavailable(): void
    {
        Cache::flush();
        $service = $this->createPartialMock(DeviceFingerprintService::class, ['getIpData']);
        $service->method('getIpData')->willReturn(null);

        $result = $service->assessIpReputation('0.0.0.0');

        $this->assertEquals(0.0, $result['risk_score']);
        $this->assertEmpty($result['flags']);
    }
}
