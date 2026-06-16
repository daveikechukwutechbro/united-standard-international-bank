<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Lightweight test case for smoke tests.
 *
 * Unlike the full TestCase, this does NOT use LazilyRefreshDatabase,
 * does NOT create roles/users/accounts, and does NOT run migrations.
 * Smoke tests verify that endpoints respond correctly without touching the database.
 */
abstract class SmokeTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Use array cache driver to avoid Redis dependency
        config(['cache.default' => 'array']);
    }
}
