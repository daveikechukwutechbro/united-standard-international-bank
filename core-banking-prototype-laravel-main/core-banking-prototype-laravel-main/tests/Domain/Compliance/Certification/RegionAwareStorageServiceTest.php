<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\RegionAwareStorageService;

describe('RegionAwareStorageService', function () {
    it('returns disk for configured region', function () {
        $service = new RegionAwareStorageService();
        $disk = $service->getDiskForRegion('EU');

        expect($disk)->toBeString();
    });

    it('returns default disk for unknown region', function () {
        $service = new RegionAwareStorageService();
        $disk = $service->getDiskForRegion('UNKNOWN');

        expect($disk)->toBe(config('filesystems.default', 'local'));
    });

    it('lists all regional disks', function () {
        $service = new RegionAwareStorageService();
        $disks = $service->getRegionalDisks();

        expect($disks)->toBeArray();
    });

    it('verifies storage access', function () {
        $service = new RegionAwareStorageService();
        $results = $service->verifyStorageAccess();

        expect($results)->toBeArray();
        foreach ($results as $region => $result) {
            expect($result)->toHaveKey('disk')
                ->and($result)->toHaveKey('accessible');
        }
    });
});
