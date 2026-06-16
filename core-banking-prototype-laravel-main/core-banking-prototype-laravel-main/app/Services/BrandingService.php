<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\FinancialInstitution\Models\PartnerBranding;

/**
 * Service for retrieving branding configuration for white-label support.
 *
 * This service provides dynamic branding values based on the current
 * partner context, falling back to default FinAegis branding when
 * no partner-specific branding is configured.
 */
class BrandingService
{
    private ?PartnerBranding $branding = null;

    /**
     * Default branding values when no partner branding is configured.
     *
     * @var array<string, mixed>
     */
    private array $defaults = [
        'company_name'         => 'FinAegis',
        'tagline'              => 'Modern Banking Infrastructure',
        'support_email'        => 'support@finaegis.org',
        'support_phone'        => null,
        'logo_url'             => null,
        'logo_dark_url'        => null,
        'favicon_url'          => null,
        'primary_color'        => '#1a365d',
        'secondary_color'      => '#2b6cb0',
        'accent_color'         => '#2b6cb0',
        'text_color'           => '#1a202c',
        'background_color'     => '#ffffff',
        'privacy_policy_url'   => null,
        'terms_of_service_url' => null,
    ];

    /**
     * Set the partner branding to use.
     */
    public function setBranding(?PartnerBranding $branding): self
    {
        $this->branding = $branding;

        return $this;
    }

    /**
     * Get the current partner branding.
     */
    public function getBranding(): ?PartnerBranding
    {
        return $this->branding;
    }

    /**
     * Check if custom partner branding is active.
     */
    public function hasCustomBranding(): bool
    {
        return $this->branding !== null && $this->branding->is_active;
    }

    /**
     * Get the company name.
     */
    public function companyName(): string
    {
        return $this->get('company_name');
    }

    /**
     * Get the support email.
     */
    public function supportEmail(): string
    {
        return $this->get('support_email');
    }

    /**
     * Get the tagline.
     */
    public function tagline(): ?string
    {
        return $this->get('tagline');
    }

    /**
     * Get the primary logo URL.
     */
    public function logoUrl(): ?string
    {
        return $this->get('logo_url');
    }

    /**
     * Get the primary color.
     */
    public function primaryColor(): string
    {
        return $this->get('primary_color');
    }

    /**
     * Get a specific branding value with fallback to default.
     */
    public function get(string $key): mixed
    {
        if ($this->hasCustomBranding() && isset($this->branding->{$key})) {
            return $this->branding->{$key} ?? $this->defaults[$key] ?? null;
        }

        return $this->defaults[$key] ?? null;
    }

    /**
     * Get all branding values as an array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = $this->defaults;

        if ($this->hasCustomBranding()) {
            foreach (array_keys($this->defaults) as $key) {
                if (isset($this->branding->{$key})) {
                    $values[$key] = $this->branding->{$key};
                }
            }
        }

        return $values;
    }

    /**
     * Get branding formatted for email templates.
     *
     * @return array<string, mixed>
     */
    public function forEmail(): array
    {
        return [
            'company_name'   => $this->companyName(),
            'support_email'  => $this->supportEmail(),
            'tagline'        => $this->tagline(),
            'logo_url'       => $this->logoUrl(),
            'primary_color'  => $this->primaryColor(),
            'team_signature' => "The {$this->companyName()} Team",
        ];
    }
}
