<?php

declare(strict_types=1);

use App\Domain\Banking\Services\IntelligentRoutingService;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->service = new IntelligentRoutingService();
});

it('selects optimal rail with score breakdown', function (): void {
    $result = $this->service->selectOptimalRail('1000.00', 'USD', 'US');

    expect($result)->toHaveKeys(['recommended_rail', 'score', 'alternatives', 'decision_factors']);
    expect($result['score'])->toBeGreaterThan(0);
    expect($result['alternatives'])->toBeArray();
});

it('returns failover chain for each rail', function (): void {
    $chain = $this->service->getFailoverChain('FEDNOW');

    expect($chain)->toBeArray();
    expect($chain)->not->toContain('FEDNOW'); // Primary not in failover
});

it('checks operating hours for 24/7 rails', function (): void {
    // RTP and FEDNOW are 24/7
    expect($this->service->isWithinOperatingHours('RTP'))->toBeTrue();
    expect($this->service->isWithinOperatingHours('FEDNOW'))->toBeTrue();
});

it('estimates costs per rail', function (): void {
    $cost = $this->service->getCostEstimate('ACH', '1000.00', 'USD');
    expect($cost)->toBeGreaterThanOrEqual(0.0);
});

it('returns success rate between 0 and 1', function (): void {
    $rate = $this->service->getSuccessRate('ACH');
    expect($rate)->toBeGreaterThanOrEqual(0.0);
    expect($rate)->toBeLessThanOrEqual(1.0);
});

it('records outcome atomically', function (): void {
    config(['cache.default' => 'array']);
    $this->service->recordOutcome('ACH', true, 150, 0.25);
    $this->service->recordOutcome('ACH', false, 5000, 0.25);

    $rate = $this->service->getSuccessRate('ACH', 1);
    expect($rate)->toBe(0.5);
});
