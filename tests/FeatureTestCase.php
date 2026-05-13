<?php

namespace Tests;

use Laravel\Ranger\RangerServiceProvider;
use Laravel\Surveyor\SurveyorServiceProvider;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

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
}
