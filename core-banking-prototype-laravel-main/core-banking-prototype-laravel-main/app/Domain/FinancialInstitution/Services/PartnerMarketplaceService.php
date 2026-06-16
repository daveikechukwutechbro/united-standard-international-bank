<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerIntegration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Manages partner marketplace integrations (connectors to third-party providers).
 */
class PartnerMarketplaceService
{
    /**
     * List available integration categories and providers from config.
     *
     * @return array<string, array<string, mixed>>
     */
    public function listAvailableIntegrations(): array
    {
        return config('baas.marketplace.integration_categories', []);
    }

    /**
     * Enable an integration for a partner.
     *
     * @param  array<string, mixed>  $integrationConfig
     * @return array{success: bool, message: string, integration: PartnerIntegration|null}
     */
    public function enableIntegration(
        FinancialInstitutionPartner $partner,
        string $category,
        string $provider,
        array $integrationConfig = [],
    ): array {
        if (! config('baas.marketplace.enabled', true)) {
            return [
                'success'     => false,
                'message'     => 'Marketplace integrations are currently disabled',
                'integration' => null,
            ];
        }

        $categories = $this->listAvailableIntegrations();

        if (! isset($categories[$category])) {
            return [
                'success'     => false,
                'message'     => "Unknown integration category: {$category}",
                'integration' => null,
            ];
        }

        if (! in_array($provider, $categories[$category]['providers'] ?? [], true)) {
            return [
                'success'     => false,
                'message'     => "Provider '{$provider}' is not available in category '{$category}'",
                'integration' => null,
            ];
        }

        // Check for existing active integration
        $existing = PartnerIntegration::where('partner_id', $partner->id)
            ->where('category', $category)
            ->where('provider', $provider)
            ->whereNull('deleted_at')
            ->first();

        if ($existing && $existing->isActive()) {
            return [
                'success'     => false,
                'message'     => "Integration for {$provider} in {$category} is already active",
                'integration' => $existing,
            ];
        }

        if ($existing) {
            $existing->update([
                'status' => 'active',
                'config' => $integrationConfig ?: $existing->config,
            ]);
            $integration = $existing->fresh();
        } else {
            $integration = PartnerIntegration::create([
                'uuid'       => (string) Str::uuid(),
                'partner_id' => $partner->id,
                'category'   => $category,
                'provider'   => $provider,
                'status'     => 'active',
                'config'     => $integrationConfig,
                'metadata'   => [],
            ]);
        }

        Log::info('Partner integration enabled', [
            'partner_id' => $partner->id,
            'category'   => $category,
            'provider'   => $provider,
        ]);

        return [
            'success'     => true,
            'message'     => "Integration for {$provider} enabled successfully",
            'integration' => $integration,
        ];
    }

    /**
     * Disable an integration for a partner.
     *
     * @return array{success: bool, message: string}
     */
    public function disableIntegration(
        FinancialInstitutionPartner $partner,
        int $integrationId,
    ): array {
        $integration = PartnerIntegration::where('partner_id', $partner->id)
            ->where('id', $integrationId)
            ->first();

        if (! $integration) {
            return [
                'success' => false,
                'message' => 'Integration not found',
            ];
        }

        $integration->update(['status' => 'disabled']);

        Log::info('Partner integration disabled', [
            'partner_id'     => $partner->id,
            'integration_id' => $integrationId,
        ]);

        return [
            'success' => true,
            'message' => "Integration for {$integration->provider} disabled",
        ];
    }

    /**
     * Get a partner's active integrations.
     *
     * @return Collection<int, PartnerIntegration>
     */
    public function getPartnerIntegrations(FinancialInstitutionPartner $partner): Collection
    {
        return PartnerIntegration::where('partner_id', $partner->id)
            ->active()
            ->get();
    }

    /**
     * Test an integration connection (demo: always succeeds).
     *
     * @return array{success: bool, message: string, latency_ms: int}
     */
    public function testConnection(
        FinancialInstitutionPartner $partner,
        int $integrationId,
    ): array {
        $integration = PartnerIntegration::where('partner_id', $partner->id)
            ->where('id', $integrationId)
            ->first();

        if (! $integration) {
            return [
                'success'    => false,
                'message'    => 'Integration not found',
                'latency_ms' => 0,
            ];
        }

        // Demo mode: simulate a connection test
        $latency = random_int(50, 200);

        $integration->markSynced();

        return [
            'success'    => true,
            'message'    => "Connection to {$integration->provider} successful",
            'latency_ms' => $latency,
        ];
    }

    /**
     * Get integration health overview for a partner.
     *
     * @return array{total: int, active: int, errored: int, health_score: float}
     */
    public function getIntegrationHealth(FinancialInstitutionPartner $partner): array
    {
        $integrations = PartnerIntegration::where('partner_id', $partner->id)
            ->whereNull('deleted_at')
            ->get();

        $total = $integrations->count();
        $active = $integrations->where('status', 'active')->count();
        $errored = $integrations->where('error_count', '>', 0)->count();

        $healthScore = $total > 0
            ? round((($active - $errored) / $total) * 100, 1)
            : 100.0;

        return [
            'total'        => $total,
            'active'       => $active,
            'errored'      => $errored,
            'health_score' => max(0.0, $healthScore),
        ];
    }
}
