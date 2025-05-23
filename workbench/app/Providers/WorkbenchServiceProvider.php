<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
