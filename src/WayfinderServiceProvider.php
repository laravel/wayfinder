<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\ServiceProvider;

class WayfinderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wayfinder.php', 'wayfinder');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/wayfinder.php' => config_path('wayfinder.php'),
            ], 'config');
        }
    }
}
