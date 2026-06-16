<?php

declare(strict_types=1);

use App\Infrastructure\Plugins\PluginManifest;

describe('PluginManifest validation', function () {
    it('rejects vendor with path traversal characters', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => '../../etc',
            'name'    => 'malicious',
            'version' => '1.0.0',
        ]);

        expect($manifest->validate())->toBeFalse();
    });

    it('rejects name with path traversal characters', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => 'legit-vendor',
            'name'    => '../../../passwd',
            'version' => '1.0.0',
        ]);

        expect($manifest->validate())->toBeFalse();
    });

    it('rejects vendor with spaces', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => 'bad vendor',
            'name'    => 'plugin',
            'version' => '1.0.0',
        ]);

        expect($manifest->validate())->toBeFalse();
    });

    it('rejects name with spaces', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => 'good-vendor',
            'name'    => 'bad plugin name',
            'version' => '1.0.0',
        ]);

        expect($manifest->validate())->toBeFalse();
    });

    it('accepts alphanumeric vendor and name with hyphens and underscores', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => 'finaegis-core',
            'name'    => 'my_plugin-v2',
            'version' => '1.0.0',
        ]);

        expect($manifest->validate())->toBeTrue();
    });

    it('accepts simple alphanumeric vendor and name', function () {
        $manifest = PluginManifest::fromArray([
            'vendor'  => 'finaegis',
            'name'    => 'analytics',
            'version' => '2.1.0',
        ]);

        expect($manifest->validate())->toBeTrue();
    });
});
