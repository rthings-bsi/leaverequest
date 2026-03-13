<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Force HTTPS
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // 2. PERBAIKAN VERCEL: Alihkan Cache & Views ke /tmp
        if (env('VIEW_COMPILED_PATH') === '/tmp') {
            // Buat subfolder di /tmp karena /tmp root terkadang punya limitasi
            $tmpViewPath = '/tmp/views';
            $tmpCachePath = '/tmp/cache';

            if (!is_dir($tmpViewPath)) {
                @mkdir($tmpViewPath, 0777, true);
            }
            if (!is_dir($tmpCachePath)) {
                @mkdir($tmpCachePath, 0777, true);
            }

            // Paksa Laravel menggunakan folder ini saat runtime
            Config::set('view.compiled', $tmpViewPath);
            Config::set('cache.stores.file.path', $tmpCachePath);

            // Pastikan Vite manifest bisa dibaca dari path yang benar
            Config::set('app.asset_url', env('APP_URL'));
        } else {
            // Logika untuk lokal (memastikan folder storage ada)
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
}