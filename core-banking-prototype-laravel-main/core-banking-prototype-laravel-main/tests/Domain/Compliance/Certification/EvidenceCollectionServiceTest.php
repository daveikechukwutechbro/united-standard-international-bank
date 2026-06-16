<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\EvidenceCollectionService;

describe('EvidenceCollectionService', function () {
    beforeEach(function () {
        $this->service = new EvidenceCollectionService();
        config(['compliance-certification.soc2.demo_mode' => true]);
    });

    it('collects evidence for a period in demo mode', function () {
        $evidence = $this->service->collectEvidence('Q1-2026');

        expect($evidence)->toBeArray()
            ->and($evidence)->not->toBeEmpty();
    });

    it('collects evidence with specific type filter', function () {
        $evidence = $this->service->collectEvidence('Q1-2026', 'access_logs');

        expect($evidence)->toBeArray();
    });

    it('generates SHA-256 integrity hash', function () {
        $data = ['test' => 'data', 'key' => 'value'];
        $hash = $this->service->generateIntegrityHash($data);

        expect($hash)->toBeString()
            ->and(strlen($hash))->toBe(64)
            ->and($hash)->toBe(hash('sha256', json_encode($data)));
    });

    it('returns consistent hashes for same data', function () {
        $data = ['foo' => 'bar'];
        $hash1 = $this->service->generateIntegrityHash($data);
        $hash2 = $this->service->generateIntegrityHash($data);

        expect($hash1)->toBe($hash2);
    });

    it('collects config snapshot evidence', function () {
        $evidence = $this->service->collectEvidence('Q1-2026', 'config_snapshot');

        expect($evidence)->toBeArray();
    });
});
