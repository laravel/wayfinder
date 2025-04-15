<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\ServiceProvider;
use Laravel\Wayfinder\Console\GenerateCommand;
use Laravel\Wayfinder\Console\InstallCommand;

class WayfinderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__.'/../config/wayfinder.php' => config_path('wayfinder.php'),
        ], 'wayfinder-config');
    }
}
