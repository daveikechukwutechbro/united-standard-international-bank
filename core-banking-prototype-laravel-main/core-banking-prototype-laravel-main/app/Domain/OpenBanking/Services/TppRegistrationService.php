<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Models\TppRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TppRegistrationService
{
    /**
     * Register a new Third-Party Provider.
     *
     * @param array{
     *     tpp_id?: string,
     *     name: string,
     *     client_id?: string,
     *     client_secret: string,
     *     eidas_certificate?: string,
     *     redirect_uris?: array<int, string>,
     *     roles?: array<int, string>,
     *     status?: string,
     * } $data
     */
    public function register(array $data): TppRegistration
    {
        return TppRegistration::create([
            'tpp_id'             => $data['tpp_id'] ?? Str::uuid()->toString(),
            'name'               => $data['name'],
            'client_id'          => $data['client_id'] ?? Str::random(32),
            'client_secret_hash' => Hash::make($data['client_secret']),
            'eidas_certificate'  => $data['eidas_certificate'] ?? null,
            'redirect_uris'      => $data['redirect_uris'] ?? [],
            'roles'              => $data['roles'] ?? [],
            'status'             => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update an existing TPP registration.
     *
     * @param array{
     *     name?: string,
     *     client_secret?: string,
     *     eidas_certificate?: string,
     *     redirect_uris?: array<int, string>,
     *     roles?: array<int, string>,
     *     status?: string,
     * } $data
     */
    public function update(string $tppId, array $data): TppRegistration
    {
        $tpp = $this->findByTppId($tppId);

        if ($tpp === null) {
            throw new InvalidArgumentException("TPP not found: {$tppId}");
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['client_secret'])) {
            $updateData['client_secret_hash'] = Hash::make($data['client_secret']);
        }

        if (array_key_exists('eidas_certificate', $data)) {
            $updateData['eidas_certificate'] = $data['eidas_certificate'];
        }

        if (isset($data['redirect_uris'])) {
            $updateData['redirect_uris'] = $data['redirect_uris'];
        }

        if (isset($data['roles'])) {
            $updateData['roles'] = $data['roles'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $tpp->update($updateData);

        return $tpp->refresh();
    }

    /**
     * Deactivate a TPP registration by setting its status to inactive.
     */
    public function deactivate(string $tppId): TppRegistration
    {
        $tpp = $this->findByTppId($tppId);

        if ($tpp === null) {
            throw new InvalidArgumentException("TPP not found: {$tppId}");
        }

        $tpp->update(['status' => 'inactive']);

        return $tpp->refresh();
    }

    /**
     * Find a TPP registration by its OAuth client_id.
     */
    public function findByClientId(string $clientId): ?TppRegistration
    {
        return TppRegistration::where('client_id', $clientId)->first();
    }

    /**
     * Find a TPP registration by its tpp_id.
     */
    public function findByTppId(string $tppId): ?TppRegistration
    {
        return TppRegistration::where('tpp_id', $tppId)->first();
    }

    /**
     * Validate that a PEM-encoded certificate appears structurally valid.
     */
    public function validateCertificate(string $certificate): bool
    {
        $trimmed = trim($certificate);

        if ($trimmed === '') {
            return false;
        }

        return str_contains($trimmed, '-----BEGIN CERTIFICATE-----');
    }
}
