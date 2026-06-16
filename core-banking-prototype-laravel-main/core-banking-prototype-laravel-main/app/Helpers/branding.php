<?php

declare(strict_types=1);

use App\Services\BrandingService;

if (! function_exists('branding')) {
    /**
     * Get the branding service instance or a specific branding value.
     *
     * @param  string|null  $key  Optional key to retrieve a specific value
     * @return BrandingService|mixed
     */
    function branding(?string $key = null): mixed
    {
        $service = app(BrandingService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key);
    }
}

if (! function_exists('company_name')) {
    /**
     * Get the current company/brand name.
     */
    function company_name(): string
    {
        return app(BrandingService::class)->companyName();
    }
}

if (! function_exists('support_email')) {
    /**
     * Get the current support email.
     */
    function support_email(): string
    {
        return app(BrandingService::class)->supportEmail();
    }
}

if (! function_exists('team_signature')) {
    /**
     * Get the team signature for emails.
     */
    function team_signature(): string
    {
        return 'The ' . app(BrandingService::class)->companyName() . ' Team';
    }
}
