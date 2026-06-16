<?php

declare(strict_types=1);

namespace Plugins\AuditExporter;

use Illuminate\Support\ServiceProvider;

class AuditExporterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            AuditExportCommand::class,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
