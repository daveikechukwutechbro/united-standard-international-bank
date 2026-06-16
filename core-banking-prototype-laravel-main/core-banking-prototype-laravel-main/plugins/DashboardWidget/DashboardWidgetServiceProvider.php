<?php

declare(strict_types=1);

namespace Plugins\DashboardWidget;

use Illuminate\Support\ServiceProvider;

class DashboardWidgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (class_exists(\Filament\Facades\Filament::class)) {
            \Filament\Facades\Filament::registerWidgets([
                DomainHealthWidget::class,
            ]);
        }
    }
}
