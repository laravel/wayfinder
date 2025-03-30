<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\ServiceProvider;

class WayfinderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
            ]);
        }
    }
}
