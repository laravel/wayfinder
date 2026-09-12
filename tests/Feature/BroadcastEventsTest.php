<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Laravel\Ranger\Collectors\Models as ModelCollector;
use Laravel\Ranger\Components\BroadcastEvent;
use Laravel\Surveyor\SurveyorServiceProvider;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Converters\BroadcastEvents;
use Laravel\Wayfinder\Converters\Models;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class BroadcastEventsTest extends TestCase
{
    #[DataProvider('broadcastEvents')]
    public function test_events_augment_existing_echo_exports_and_infer_payloads(string $echoPackage, string $eventName, bool $withModel): void
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

            $app = new Application($tempPath);
            $app->instance('config', new Repository);
            $app->register(SurveyorServiceProvider::class);

            if (! ResultConverter::getRegistry()->hasConverter(TypeScriptConverter::class)) {
                ResultConverter::register(TypeScriptConverter::class);
            }

            $payload = [
                'orderId' => Type::int(),
                'Order.Shipped' => Type::int(),
                'Shipment "Order.Shipped"' => Type::int(),
            ];

            if ($withModel) {
                $models = $app->make(ModelCollector::class)
                    ->setAppPaths($rootPath.'/workbench/app/Models')
                    ->collect();

                foreach ($models as $model) {
                    (new Models)->convert($model);
                }

                $payload['user'] = new ClassType(User::class);
            }

            $event = (new BroadcastEvent(
                $eventName,
                'App\\Events\\OrderShipped',
                Type::array($payload),
            ))->setFilePath($rootPath.'/workbench/app/Events/OrderShipped.php');

            foreach ((new BroadcastEvents)->convert(collect([$event])) as $result) {
                $files->put($tempPath.'/'.$result->name, $result->content());
            }

            $files->put($tempPath.'/types.ts', TypeScript::getNamespacedFormatted()->implode(PHP_EOL));

            $eventPath = str_replace('\\', '.', $eventName);
            $modelAssertions = $withModel ? <<<'TS'
                    event.user.id.toFixed();
                    // @ts-expect-error Referenced model attributes must retain their types.
                    event.user.id.toUpperCase();
                TS : '';

            $files->put($tempPath.'/consumer.ts', <<<TS
                import { useEcho } from "{$echoPackage}";
                import { BroadcastEvents } from "./broadcast-events";

                useEcho("orders.1", BroadcastEvents.{$eventPath}, (event) => {
                    event.orderId.toFixed();
                    event["Order.Shipped"].toFixed();
                    event['Shipment "Order.Shipped"'].toFixed();
                    // @ts-expect-error Event payloads must retain their generated types.
                    event.orderId.toUpperCase();
                    // @ts-expect-error Unknown payload properties must be rejected.
                    event.missing;
                    {$modelAssertions}
                });
                TS);
            $files->put($tempPath.'/tsconfig.json', json_encode([
                'extends' => $rootPath.'/tsconfig.json',
                'compilerOptions' => ['rootDir' => '.', 'skipLibCheck' => false],
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

    public static function broadcastEvents(): array
    {
        return [
            'React scalar alias' => ['@laravel/echo-react', 'order.shipped', false],
            'Vue scalar alias' => ['@laravel/echo-vue', 'order.shipped', false],
            'React model alias' => ['@laravel/echo-react', 'order.shipped', true],
            'Vue model alias' => ['@laravel/echo-vue', 'order.shipped', true],
            'React cross-namespace model' => ['@laravel/echo-react', 'Domain\\Events\\OrderShipped', true],
            'Vue cross-namespace model' => ['@laravel/echo-vue', 'Domain\\Events\\OrderShipped', true],
        ];
    }
}
