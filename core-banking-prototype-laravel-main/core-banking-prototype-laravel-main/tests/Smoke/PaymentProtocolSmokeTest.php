<?php

declare(strict_types=1);

uses(Tests\SmokeTestCase::class);

describe('Payment Protocol Smoke Tests', function () {
    it('returns x402 configuration with valid JSON structure when available', function () {
        $response = $this->getJson('/api/.well-known/x402-configuration');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('x402 configuration not available (status: ' . $response->getStatusCode() . ')');
        }

        $data = $response->json();
        expect($data)->toBeArray()->not->toBeEmpty();
    });

    it('returns mpp configuration with valid JSON structure when available', function () {
        $response = $this->getJson('/api/.well-known/mpp-configuration');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('MPP configuration not available (status: ' . $response->getStatusCode() . ')');
        }

        $data = $response->json();
        expect($data)->toBeArray()->not->toBeEmpty();
    });

    it('returns A2A agent card as valid JSON', function () {
        $response = $this->getJson('/api/.well-known/agent.json');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('A2A agent card not available (status: ' . $response->getStatusCode() . ')');
        }

        $data = $response->json();
        expect($data)->toBeArray()->not->toBeEmpty();
    });

    it('returns ap2 configuration without errors when available', function () {
        $response = $this->getJson('/api/.well-known/ap2-configuration');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('AP2 configuration not available (status: ' . $response->getStatusCode() . ')');
        }

        $data = $response->json();
        expect($data)->toBeArray();
    });
});
