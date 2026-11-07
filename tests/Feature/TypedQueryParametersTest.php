<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

it('keeps query typing opt-in', function () {
    $root = dirname(__DIR__, 2);
    expect((require $root.'/config/wayfinder.php')['generate']['route']['typed_query'])->toBeFalse();
    $output = sys_get_temp_dir().'/wayfinder-query-'.uniqid();

    try {
        foreach (['false', 'true'] as $enabled) {
            $process = new Process([
                PHP_BINARY, $root.'/vendor/bin/testbench', 'wayfinder:generate',
                '--path='.$output, '--app-path='.$root.'/workbench/app',
                '--base-path='.$root.'/workbench',
            ], $root, ['WAYFINDER_GENERATE_TYPED_QUERY' => $enabled]);
            $process->setTimeout(60);
            $process->mustRun();

            $contents = file_get_contents($output.'/routes/orders/index.ts');

            if ($enabled === 'true') {
                expect($contents)->toContain('RouteQueryOptions<WayfinderQueryTypes.App.Http.Controllers.OrderController.Index.Request>');
                expect($contents)->toContain('import type * as WayfinderQueryTypes');
            } else {
                expect($contents)->not->toContain('RouteQueryOptions<');
                expect($contents)->not->toContain('WayfinderQueryTypes');
            }
        }
    } finally {
        (new Filesystem)->deleteDirectory($output);
    }
});
