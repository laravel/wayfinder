<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\ServiceProvider;

class WayfinderServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (! app()->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/wayfinder.php', 'wayfinder');
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/wayfinder.php' => config_path('wayfinder.php'),
        ], 'wayfinder-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
            ]);
        }
    }
}
