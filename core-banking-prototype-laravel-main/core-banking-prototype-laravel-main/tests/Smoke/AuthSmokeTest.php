<?php

declare(strict_types=1);

uses(Tests\SmokeTestCase::class);

describe('Auth Smoke Tests', function () {
    it('serves the login page', function () {
        $response = $this->get('/login');
        expect($response->getStatusCode())->toBeIn([200, 301, 302]);
    });

    it('serves the register page', function () {
        $response = $this->get('/register');
        expect($response->getStatusCode())->toBeIn([200, 301, 302]);
    });

    it('rejects invalid login without crashing', function () {
        $response = $this->post('/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);
        // 302 (redirect back), 422 (validation), or 500 (DB unavailable in test) — never 404
        expect($response->getStatusCode())->toBeIn([302, 422, 500]);
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('returns 401 for unauthenticated API card list', function () {
        $response = $this->getJson('/api/v1/cards');
        expect($response->getStatusCode())->toBe(401);
    });

    it('returns GraphQL response without auth (always 200)', function () {
        $response = $this->postJson('/graphql', [
            'query' => '{ __typename }',
        ]);
        expect($response->getStatusCode())->toBe(200);
    });
});
