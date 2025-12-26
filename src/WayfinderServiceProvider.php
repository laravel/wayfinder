<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\ServiceProvider;

class WayfinderServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->mergeConfigFrom(__DIR__.'/../config/wayfinder.php', 'wayfinder');
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__.'/../config/wayfinder.php' => config_path(
                        'wayfinder.php',
                    ),
                ],
                'wayfinder-config',
            );
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\GenerateCommand::class,
            ]);
        }
    }
}
