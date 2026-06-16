<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use RuntimeException;

/**
 * Validates outbound URLs to prevent SSRF attacks.
 *
 * Rejects URLs that resolve to private/internal IP ranges, cloud metadata
 * endpoints, and non-HTTPS schemes in production. Used for webhook
 * registration and any user-supplied callback URLs.
 */
class UrlValidator
{
    /**
     * Cloud metadata endpoints that must always be blocked.
     *
     * @var list<string>
     */
    private const BLOCKED_HOSTS = [
        '169.254.169.254',           // AWS metadata
        'metadata.google.internal',  // GCP metadata
        '100.100.100.200',           // Alibaba metadata
    ];

    /**
     * Validate that a URL is safe for outbound requests (not internal/private).
     *
     * @throws RuntimeException if the URL is invalid or points to an internal resource
     */
    public static function validateExternalUrl(string $url): void
    {
        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['host'])) {
            throw new RuntimeException('Invalid URL format');
        }

        $host = $parsed['host'];
        $scheme = $parsed['scheme'] ?? '';

        // Must be HTTPS in production
        if (app()->environment('production') && $scheme !== 'https') {
            throw new RuntimeException('Webhook URLs must use HTTPS in production');
        }

        // Block known metadata endpoints
        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new RuntimeException('URL points to blocked host');
        }

        // Resolve hostname and check against private ranges
        $ips = gethostbynamel($host);
        if ($ips === false) {
            throw new RuntimeException('Could not resolve hostname');
        }

        foreach ($ips as $ip) {
            if (self::isPrivateIp($ip)) {
                throw new RuntimeException('URL resolves to private/internal IP address');
            }
        }
    }

    /**
     * Check if an IP address is in a private or reserved range.
     *
     * Uses PHP's built-in FILTER_VALIDATE_IP with NO_PRIV_RANGE and NO_RES_RANGE
     * flags, which covers RFC 1918, loopback, link-local, and other reserved ranges.
     */
    private static function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
