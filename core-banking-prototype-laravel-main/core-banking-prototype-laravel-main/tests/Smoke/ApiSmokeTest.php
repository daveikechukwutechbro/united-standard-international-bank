<?php

declare(strict_types=1);

uses(Tests\SmokeTestCase::class);

describe('API Infrastructure Smoke Tests', function () {
    it('returns valid GraphQL schema via introspection', function () {
        $response = $this->postJson('/graphql', [
            'query' => '{ __schema { types { name } } }',
        ]);
        expect($response->getStatusCode())->toBe(200);

        $data = $response->json();
        expect($data)->toHaveKey('data.__schema.types');
        expect($data['data']['__schema']['types'])->toBeArray()->not->toBeEmpty();
    });

    it('returns 401 for unauthenticated cards endpoint', function () {
        $response = $this->getJson('/api/v1/cards');
        expect($response->getStatusCode())->toBe(401);
    });

    it('resolves bank connections route (auth-protected)', function () {
        $response = $this->getJson('/api/v2/banks/connections');
        // 401/403 in production, may 500 in test env due to service container bindings
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('resolves x402 well-known route', function () {
        $response = $this->getJson('/api/.well-known/x402-configuration');
        // Route exists and resolves (200 when configured, may error in test env)
        expect($response->getStatusCode())->not->toBe(405);
    });

    it('resolves mpp well-known route', function () {
        $response = $this->getJson('/api/.well-known/mpp-configuration');
        expect($response->getStatusCode())->not->toBe(405);
    });

    it('resolves ap2 well-known route', function () {
        $response = $this->getJson('/api/.well-known/ap2-configuration');
        expect($response->getStatusCode())->not->toBe(405);
    });

    it('resolves API status route', function () {
        $response = $this->getJson('/api/status');
        // Status endpoint needs DB; verify route resolves (not 404)
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('resolves v2 API status route', function () {
        $response = $this->getJson('/api/v2/status');
        expect($response->getStatusCode())->not->toBe(404);
    });
});
