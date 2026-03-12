<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in URLs when APP_URL is HTTPS (useful behind ngrok/SSL tunnels)
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        if (class_exists(Panel::class) && class_exists(Filament::class)) {
            try {
                if ($this->app->bound(PanelRegistry::class)) {
                    app(PanelRegistry::class)->register(Panel::make()->id('admin')->default());
                } else {
                    Filament::registerPanel(Panel::make()->id('admin')->default());
                }
            } catch (\Throwable $e) {
                // Swallow: if registration fails, don't break app boot
            }
        }

        $dirs = [
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('logs'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }
}
