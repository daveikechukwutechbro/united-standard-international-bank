<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use Illuminate\Support\Facades\Log;

/**
 * Manages embeddable widget generation with partner branding.
 */
class EmbeddableWidgetService
{
    public function __construct(
        private readonly PartnerTierService $tierService,
    ) {
    }

    /**
     * Generate embed code snippet for a widget type.
     *
     * @param  array<string, mixed>  $options
     * @return array{success: bool, message: string, html: string|null, widget_type: string}
     */
    public function generateEmbedCode(
        FinancialInstitutionPartner $partner,
        string $widgetType,
        array $options = [],
    ): array {
        $validation = $this->validateWidgetAccess($partner, $widgetType);

        if (! $validation['allowed']) {
            return [
                'success'     => false,
                'message'     => (string) $validation['reason'],
                'html'        => null,
                'widget_type' => $widgetType,
            ];
        }

        $widgetConfig = $this->getWidgetTypeConfig($widgetType);
        $branding = $partner->branding;
        $brandingConfig = $branding ? $branding->getWidgetConfig() : $this->getDefaultBrandingConfig();
        $cssVars = $branding ? $branding->getCssVariablesString() : '';

        $domain = $partner->sandbox_enabled
            ? config('baas.widgets.sandbox_domain', 'sandbox.finaegis.com')
            : config('baas.widgets.production_domain', 'api.finaegis.com');

        $containerId = $options['container_id'] ?? 'finaegis-widget';
        $width = $options['width'] ?? '100%';
        $height = $options['height'] ?? '400px';

        $configJson = json_encode([
            'partnerId'  => $partner->api_client_id,
            'widgetType' => $widgetType,
            'branding'   => $brandingConfig,
            'options'    => $options,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $jsFile = $widgetConfig['js_file'];

        $html = <<<HTML
<!-- FinAegis {$widgetConfig['name']} Widget -->
<div id="{$containerId}" style="width: {$width}; height: {$height}; {$cssVars}"></div>
<script src="https://{$domain}/widgets/{$jsFile}"></script>
<script>
  FinAegis.init({$configJson});
  FinAegis.render('{$containerId}');
</script>
HTML;

        Log::info('Widget embed code generated', [
            'partner_id'  => $partner->id,
            'widget_type' => $widgetType,
        ]);

        return [
            'success'     => true,
            'message'     => "Embed code generated for {$widgetConfig['name']}",
            'html'        => $html,
            'widget_type' => $widgetType,
        ];
    }

    /**
     * Get available widget types from config.
     *
     * @return array<string, array<string, string>>
     */
    public function getAvailableWidgets(): array
    {
        return config('baas.widgets.types', []);
    }

    /**
     * Generate the JavaScript content for a widget.
     *
     * @return array{success: bool, message: string, content: string|null, widget_type: string}
     */
    public function generateWidgetScript(
        FinancialInstitutionPartner $partner,
        string $widgetType,
    ): array {
        $validation = $this->validateWidgetAccess($partner, $widgetType);

        if (! $validation['allowed']) {
            return [
                'success'     => false,
                'message'     => (string) $validation['reason'],
                'content'     => null,
                'widget_type' => $widgetType,
            ];
        }

        $widgetConfig = $this->getWidgetTypeConfig($widgetType);
        $branding = $partner->branding;
        $brandingConfig = $branding ? $branding->getWidgetConfig() : $this->getDefaultBrandingConfig();

        $brandingJson = (string) json_encode($brandingConfig, JSON_UNESCAPED_SLASHES);

        $content = <<<JS
/**
 * FinAegis {$widgetConfig['name']} Widget
 * Auto-generated for {$partner->institution_name}
 */
(function() {
  'use strict';

  var FinAegis = window.FinAegis || {};
  var defaultBranding = {$brandingJson};

  FinAegis.init = function(config) {
    this.config = Object.assign({ branding: defaultBranding }, config);
  };

  FinAegis.render = function(containerId) {
    var container = document.getElementById(containerId);
    if (!container) { console.error('FinAegis: Container not found:', containerId); return; }
    container.innerHTML = '<div class="finaegis-widget finaegis-{$widgetType}">' +
      '<div class="finaegis-header">' + (this.config.branding.company_name || 'FinAegis') + '</div>' +
      '<div class="finaegis-body">Widget loading...</div>' +
      '</div>';
  };

  window.FinAegis = FinAegis;
})();
JS;

        return [
            'success'     => true,
            'message'     => "Widget script generated for {$widgetConfig['name']}",
            'content'     => $content,
            'widget_type' => $widgetType,
        ];
    }

    /**
     * Generate a full HTML preview page for a widget.
     *
     * @return array{success: bool, message: string, html: string|null, widget_type: string}
     */
    public function previewWidget(
        FinancialInstitutionPartner $partner,
        string $widgetType,
    ): array {
        $validation = $this->validateWidgetAccess($partner, $widgetType);

        if (! $validation['allowed']) {
            return [
                'success'     => false,
                'message'     => (string) $validation['reason'],
                'html'        => null,
                'widget_type' => $widgetType,
            ];
        }

        $widgetConfig = $this->getWidgetTypeConfig($widgetType);
        $branding = $partner->branding;
        $cssVars = $branding ? $branding->getCssVariablesString() : '';
        $companyName = $branding->company_name ?? $partner->institution_name;
        $logoUrl = $branding->logo_url ?? '';

        $embedResult = $this->generateEmbedCode($partner, $widgetType);
        $embedHtml = $embedResult['html'] ?? '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$companyName} - {$widgetConfig['name']} Preview</title>
  <style>
    :root { {$cssVars} }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
    .preview-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .preview-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #eee; }
    .preview-header img { height: 32px; }
    .preview-header h1 { font-size: 18px; margin: 0; }
    .preview-badge { display: inline-block; background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
  </style>
</head>
<body>
  <div class="preview-container">
    <div class="preview-header">
      <img src="{$logoUrl}" alt="{$companyName}" onerror="this.style.display='none'">
      <h1>{$companyName}</h1>
      <span class="preview-badge">Preview</span>
    </div>
    {$embedHtml}
  </div>
</body>
</html>
HTML;

        return [
            'success'     => true,
            'message'     => "Preview generated for {$widgetConfig['name']}",
            'html'        => $html,
            'widget_type' => $widgetType,
        ];
    }

    /**
     * Validate that a partner can access a widget type.
     *
     * @return array{allowed: bool, reason: string|null}
     */
    public function validateWidgetAccess(FinancialInstitutionPartner $partner, string $widgetType): array
    {
        $tier = $this->tierService->getPartnerTier($partner);

        if (! $tier->hasWidgets()) {
            return [
                'allowed' => false,
                'reason'  => "Widget access requires Growth or Enterprise tier. Current tier: {$tier->label()}",
            ];
        }

        $availableWidgets = $this->getAvailableWidgets();

        if (! isset($availableWidgets[$widgetType])) {
            return [
                'allowed' => false,
                'reason'  => "Unknown widget type: {$widgetType}",
            ];
        }

        if (! config('baas.widgets.enabled', true)) {
            return [
                'allowed' => false,
                'reason'  => 'Widgets are currently disabled',
            ];
        }

        return [
            'allowed' => true,
            'reason'  => null,
        ];
    }

    /**
     * Get configuration for a specific widget type.
     *
     * @return array<string, string>
     */
    private function getWidgetTypeConfig(string $widgetType): array
    {
        return config("baas.widgets.types.{$widgetType}", [
            'name'        => ucfirst($widgetType),
            'description' => "{$widgetType} widget",
            'js_file'     => "finaegis-{$widgetType}.js",
        ]);
    }

    /**
     * Get default branding config when partner has no branding set.
     *
     * @return array<string, mixed>
     */
    private function getDefaultBrandingConfig(): array
    {
        return [
            'colors' => [
                '--fa-primary-color'    => '#1a73e8',
                '--fa-secondary-color'  => '#5f6368',
                '--fa-accent-color'     => '#1a73e8',
                '--fa-text-color'       => '#202124',
                '--fa-background-color' => '#ffffff',
            ],
            'logo'          => null,
            'logo_dark'     => null,
            'company_name'  => 'FinAegis',
            'tagline'       => null,
            'support_email' => 'support@finaegis.com',
        ];
    }
}
