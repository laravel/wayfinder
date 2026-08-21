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

        Config::set([
            'features.fake_source_provider' => env('WORKBENCH_FAKE_SOURCE_PROVIDER', false),
            'features.hide_retired_source_provider' => env('WORKBENCH_HIDE_RETIRED_SOURCE_PROVIDER', true),
        ]);

        URL::defaults([
            'defaultDomain' => 'tim.macdonald',
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
