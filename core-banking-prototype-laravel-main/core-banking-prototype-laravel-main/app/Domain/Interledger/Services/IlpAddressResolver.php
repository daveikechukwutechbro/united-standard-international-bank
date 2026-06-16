<?php

declare(strict_types=1);

namespace App\Domain\Interledger\Services;

/**
 * Maps FinAegis account identifiers and payment pointers to ILP addresses.
 */
class IlpAddressResolver
{
    /**
     * Resolve a FinAegis account ID to a fully-qualified ILP address.
     *
     * @return string e.g. g.finaegis.user.{uuid}
     */
    public function resolve(string $accountId): string
    {
        $prefix = (string) config('interledger.ilp_address', 'g.finaegis');

        return $prefix . '.user.' . $accountId;
    }

    /**
     * Convert a payment pointer ($wallet.example.com/alice) to an ILP address.
     *
     * Per the Open Payments / SPSP spec a payment pointer is a URL shorthand:
     *   $wallet.example.com        → https://wallet.example.com/
     *   $wallet.example.com/alice  → https://wallet.example.com/alice
     *
     * We derive a deterministic ILP address from the host + path segments.
     */
    public function fromPaymentPointer(string $pointer): string
    {
        if (! str_starts_with($pointer, '$')) {
            return $pointer;
        }

        // Strip the leading '$' and parse the remainder as a URL.
        $url = 'https://' . substr($pointer, 1);
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
        $path = ltrim((string) (parse_url($url, PHP_URL_PATH) ?? ''), '/');

        /** @var string[] $parts */
        $parts = array_values(array_filter([$host, $path], static fn (mixed $s): bool => $s !== ''));

        // Build a dotted ILP address: g.{host-segments}.{path-segments}
        $segments = implode('.', array_map(
            static fn (mixed $part): string => str_replace(['/', '-', '_'], '.', (string) $part),
            $parts,
        ));

        return 'g.' . $segments;
    }

    /**
     * Convert an ILP address back to a payment pointer.
     *
     * This is the inverse of fromPaymentPointer for the simple case where the
     * ILP address was produced by this resolver.  For addresses of the form
     * g.finaegis.user.{id} the resulting pointer will be $finaegis/user/{id}.
     */
    public function toPaymentPointer(string $ilpAddress): string
    {
        // Strip the leading "g." interledger allocation scheme prefix.
        $without = preg_replace('/^g\./', '', $ilpAddress) ?? $ilpAddress;

        // Split on dots; first segment becomes the host, the rest form the path.
        $segments = explode('.', $without);
        $host = array_shift($segments);
        $path = implode('/', $segments);

        return '$' . $host . ($path !== '' ? '/' . $path : '');
    }
}
