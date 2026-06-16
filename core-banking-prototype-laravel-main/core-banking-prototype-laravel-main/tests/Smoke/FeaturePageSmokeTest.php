<?php

declare(strict_types=1);

uses(Tests\SmokeTestCase::class);

describe('Feature Page Smoke Tests', function () {
    beforeEach(function () {
        if (! config('brand.show_promo_pages')) {
            $this->markTestSkipped('Promo pages disabled');
        }
    });

    it('serves the features index page', function () {
        $response = $this->get('/features');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the bank integration feature page', function () {
        $response = $this->get('/features/bank-integration');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the crosschain defi feature page', function () {
        $response = $this->get('/features/crosschain-defi');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the privacy identity feature page', function () {
        $response = $this->get('/features/privacy-identity');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the multi-tenancy feature page', function () {
        $response = $this->get('/features/multi-tenancy');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the mobile payments feature page', function () {
        $response = $this->get('/features/mobile-payments');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the machine payments feature page', function () {
        $response = $this->get('/features/machine-payments');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the pricing page', function () {
        $response = $this->get('/pricing');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the security page', function () {
        $response = $this->get('/security');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the developers page', function () {
        $response = $this->get('/developers');
        expect($response->getStatusCode())->toBe(200);
    });

    it('serves the compliance page', function () {
        $response = $this->get('/compliance');
        expect($response->getStatusCode())->toBe(200);
    });

    it('returns 404 for invalid feature slug', function () {
        $response = $this->get('/features/nonexistent-page');
        expect($response->getStatusCode())->toBe(404);
    });
});
