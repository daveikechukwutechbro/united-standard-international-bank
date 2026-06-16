<?php

declare(strict_types=1);

uses(Tests\SmokeTestCase::class);

describe('Health Check Smoke Tests', function () {
    it('serves the homepage', function () {
        $response = $this->get('/');
        expect($response->getStatusCode())->toBeIn([200, 301, 302]);
    });

    it('serves the app landing page', function () {
        $response = $this->get('/app');
        expect($response->getStatusCode())->toBeIn([200, 301, 302]);
    });

    it('resolves the status page route', function () {
        $response = $this->get('/status');
        // Status page queries DB for health checks; verify route resolves (not 404)
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('serves sitemap.xml with XML content type', function () {
        $response = $this->get('/sitemap.xml');
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toContain('xml');
    });

    it('serves robots.txt with text content type', function () {
        $response = $this->get('/robots.txt');
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toContain('text');
    });

    it('serves apple-app-site-association with JSON', function () {
        $response = $this->get('/.well-known/apple-app-site-association');
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toContain('json');
    });

    it('serves assetlinks.json with JSON', function () {
        $response = $this->get('/.well-known/assetlinks.json');
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toContain('json');
    });
});
