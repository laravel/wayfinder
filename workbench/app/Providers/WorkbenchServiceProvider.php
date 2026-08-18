<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Config::set([
            'filesystems.disks.export' => [
                'driver' => 'local',
                'root' => database_path('data/exports'),
                'serve' => true,
                'throw' => false,
            ],
        ]);

        URL::defaults([
            'defaultDomain' => 'tim.macdonald',
            // Non-scalar URL::defaults() values are coerced to '' at generate time.
            'emptyUrlDefault' => new class
            {
            },
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($root = env('WAYFINDER_FORCE_ROOT_URL')) {
            URL::forceRootUrl($root);
        }
    }
}
