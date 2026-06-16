<?php

declare(strict_types=1);

use App\Infrastructure\Security\UrlValidator;

uses(Tests\TestCase::class);

it('rejects AWS metadata endpoint', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://169.254.169.254/latest/meta-data/'))
        ->toThrow(RuntimeException::class, 'URL points to blocked host');
});

it('rejects GCP metadata endpoint', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://metadata.google.internal/computeMetadata/v1/'))
        ->toThrow(RuntimeException::class, 'URL points to blocked host');
});

it('rejects Alibaba metadata endpoint', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://100.100.100.200/latest/meta-data/'))
        ->toThrow(RuntimeException::class, 'URL points to blocked host');
});

it('rejects loopback address 127.0.0.1', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://127.0.0.1/admin'))
        ->toThrow(RuntimeException::class, 'URL resolves to private/internal IP address');
});

it('rejects private IP 10.x.x.x', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://10.0.0.1/internal'))
        ->toThrow(RuntimeException::class, 'URL resolves to private/internal IP address');
});

it('rejects private IP 192.168.x.x', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://192.168.1.1/router'))
        ->toThrow(RuntimeException::class, 'URL resolves to private/internal IP address');
});

it('rejects private IP 172.16.x.x', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://172.16.0.1/internal'))
        ->toThrow(RuntimeException::class, 'URL resolves to private/internal IP address');
});

it('rejects 0.0.0.0', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('http://0.0.0.0/'))
        ->toThrow(RuntimeException::class, 'URL resolves to private/internal IP address');
});

it('rejects invalid URL format', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('not-a-url'))
        ->toThrow(RuntimeException::class, 'Invalid URL format');
});

it('rejects URL without host', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('file:///etc/passwd'))
        ->toThrow(RuntimeException::class, 'Invalid URL format');
});

it('rejects unresolvable hostname', function (): void {
    expect(fn () => UrlValidator::validateExternalUrl('https://this-domain-definitely-does-not-exist-xyz123.invalid/webhook'))
        ->toThrow(RuntimeException::class, 'Could not resolve hostname');
});

it('accepts valid external HTTPS URL', function (): void {
    // example.com resolves to 93.184.216.34 — a public IP
    UrlValidator::validateExternalUrl('https://example.com/webhook');

    // If we get here without exception, the test passes
    expect(true)->toBeTrue();
});

it('rejects non-HTTPS in production', function (): void {
    // Temporarily set environment to production
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => UrlValidator::validateExternalUrl('http://example.com/webhook'))
        ->toThrow(RuntimeException::class, 'Webhook URLs must use HTTPS in production');

    // Restore test environment
    app()->detectEnvironment(fn () => 'testing');
});

it('allows HTTP in non-production environments', function (): void {
    // In testing environment, HTTP should be allowed (for local dev)
    app()->detectEnvironment(fn () => 'testing');

    // example.com resolves to a public IP
    UrlValidator::validateExternalUrl('http://example.com/webhook');

    expect(true)->toBeTrue();
});
