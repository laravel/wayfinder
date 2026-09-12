<?php

namespace Tests\Feature;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Laravel\Ranger\Components\BroadcastEvent;
use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Converters\BroadcastEvents;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class BroadcastEventsTest extends TestCase
{
    #[DataProvider('echoPackages')]
    public function test_custom_events_augment_existing_echo_exports_and_infer_payloads(string $echoPackage): void
    {
        $rootPath = dirname(__DIR__, 2);
        $tempPath = $rootPath.'/node_modules/.cache/wayfinder-echo-'.uniqid();
        $files = new Filesystem;
        $container = Container::getInstance();

        try {
            $files->ensureDirectoryExists($tempPath);
            $files->put($tempPath.'/package.json', json_encode([
                'dependencies' => [$echoPackage => '*'],
            ]));

            new Application($tempPath);

            if (! ResultConverter::getRegistry()->hasConverter(TypeScriptConverter::class)) {
                ResultConverter::register(TypeScriptConverter::class);
            }

            $event = (new BroadcastEvent(
                'order.shipped',
                'App\\Events\\OrderShipped',
                Type::array(['orderId' => Type::int()]),
            ))->setFilePath($rootPath.'/workbench/app/Events/OrderShipped.php');

            foreach ((new BroadcastEvents)->convert(collect([$event])) as $result) {
                $files->put($tempPath.'/'.$result->name, $result->content());
            }

            $files->put($tempPath.'/consumer.ts', <<<TS
                import { useEcho } from "{$echoPackage}";
                import { BroadcastEvents } from "./broadcast-events";

                useEcho("orders.1", BroadcastEvents.order.shipped, (event) => {
                    event.orderId.toFixed();
                    // @ts-expect-error Event payloads must retain their generated types.
                    event.orderId.toUpperCase();
                    // @ts-expect-error Unknown payload properties must be rejected.
                    event.missing;
                });
                TS);
            $files->put($tempPath.'/tsconfig.json', json_encode([
                'extends' => $rootPath.'/tsconfig.json',
                'compilerOptions' => ['rootDir' => '.'],
                'include' => ['*.ts'],
                'exclude' => [],
            ]));

            $process = new Process([$rootPath.'/node_modules/.bin/tsc', '--noEmit', '-p', $tempPath], $rootPath);
            $process->setTimeout(60);
            $process->run();

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
        } finally {
            Container::setInstance($container);
            $files->deleteDirectory($tempPath);
        }
    }

    public static function echoPackages(): array
    {
        return [
            'React' => ['@laravel/echo-react'],
            'Vue' => ['@laravel/echo-vue'],
        ];
    }
}
