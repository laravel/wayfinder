<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Laravel\Ranger\RangerServiceProvider;
use Laravel\Surveyor\SurveyorServiceProvider;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

class FeatureTestCase extends TestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            RangerServiceProvider::class,
            SurveyorServiceProvider::class,
            WayfinderServiceProvider::class,
        ];
    }

    private static ?string $cacheDirectory = null;

    protected function defineEnvironment($app): void
    {
        // A cache that outlived this run could hold analysis from an older
        // surveyor or ranger, so each run starts cold and shares one cache.
        if (static::$cacheDirectory === null) {
            static::$cacheDirectory = join_paths(sys_get_temp_dir(), 'wayfinder-test-cache-'.getmypid().'-'.uniqid());

            register_shutdown_function(fn () => (new Filesystem)->deleteDirectory(static::$cacheDirectory));
        }

        $app['config']->set('wayfinder.cache.directory', static::$cacheDirectory);
    }
}
