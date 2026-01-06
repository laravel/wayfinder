<?php

namespace Laravel\Wayfinder\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Laravel\Ranger\Ranger;
use Laravel\Ranger\Support\Config as RangerConfig;
use Laravel\Surveyor\Analyzer\AnalyzedCache;
use Laravel\Wayfinder\Converters\BroadcastChannels;
use Laravel\Wayfinder\Converters\BroadcastEvents;
use Laravel\Wayfinder\Converters\Enums;
use Laravel\Wayfinder\Converters\EnvironmentVariables;
use Laravel\Wayfinder\Converters\InertiaSharedData;
use Laravel\Wayfinder\Converters\Models;
use Laravel\Wayfinder\Converters\Routes;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Langs\TypeScript\Import;
use Laravel\Wayfinder\Langs\TypeScript\Imports;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use Laravel\Wayfinder\Support\Path;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class GenerateCommand extends Command
{
    protected $signature = 'wayfinder:generate {--path=} {--base-path=} {--app-path=} {--fresh}';

    protected $description = 'Generate TypeScript files for your Laravel application';

    protected string $generatedDirectory;

    /**
     * @var Result[]
     */
    protected $results = [];

    public function __construct(
        protected Ranger $ranger,
        protected Filesystem $files,
        protected Repository $config,
    ) {
        parent::__construct();
    }

    public function handle(
        Models $modelConverter,
        InertiaSharedData $inertiaSharedDataConverter,
        BroadcastChannels $broadcastChannelsConverter,
        BroadcastEvents $broadcastEventsConverter,
        EnvironmentVariables $environmentVariablesConverter,
        Enums $enumConverter,
        Routes $routesConverter,
    ) {
        AnalyzedCache::setCacheDirectory($this->config->get('wayfinder.cache.directory'));

        if ($this->option('fresh') || ! $this->config->get('wayfinder.cache.enabled')) {
            AnalyzedCache::clear();
        } else {
            AnalyzedCache::enable();
        }

        ResultConverter::register(TypeScriptConverter::class);

        $this->generatedDirectory = $this->option('path') ?? resource_path('js/wayfinder');

        $basePaths = $this->getBasePaths();
        $appPaths = $this->getAppPaths();

        $this->ranger->setBasePaths(...$basePaths);
        Path::setBasePaths(...$basePaths);

        $this->ranger->setAppPaths(...$appPaths);
        Path::setAppPaths(...$appPaths);

        if ($this->config->get('wayfinder.generate.models', true)) {
            $this->ranger->onModel(fn ($model) => $this->results[] = $modelConverter->convert($model));
        }

        if ($this->config->get('wayfinder.generate.inertia.shared_data', true)) {
            $this->ranger->onInertiaSharedData(
                fn ($data) => array_push(
                    $this->results,
                    ...$inertiaSharedDataConverter->convert($data),
                ),
            );
        }

        if ($this->config->get('wayfinder.generate.broadcast.channels', true)) {
            $this->ranger->onBroadcastChannels(
                fn ($channels) => $this->results[] = $broadcastChannelsConverter->convert($channels),
            );
        }

        if ($this->config->get('wayfinder.generate.broadcast.events', true)) {
            $this->ranger->onBroadcastEvents(
                fn ($events) => array_push(
                    $this->results,
                    ...$broadcastEventsConverter->convert($events),
                ),
            );
        }

        if ($this->config->get('wayfinder.generate.environment_variables', true)) {
            $this->ranger->onEnvironmentVariables(
                fn ($channels) => $this->results[] = $environmentVariablesConverter->convert($channels),
            );
        }

        if ($this->config->get('wayfinder.generate.enums', true)) {
            $this->ranger->onEnum(fn ($enum) => $this->results[] = $enumConverter->convert($enum));
        }

        $routesConverter->generateRoutes($this->config->get('wayfinder.generate.route.named', true));
        $routesConverter->generateActions($this->config->get('wayfinder.generate.route.actions', true));
        $routesConverter->withForm($this->config->get('wayfinder.generate.route.form_variant', true));
        RangerConfig::set('routes.ignore_names', $this->config->get('wayfinder.generate.route.ignore.names', []));
        RangerConfig::set('routes.ignore_urls', $this->config->get('wayfinder.generate.route.ignore.urls', []));

        $this->ranger->onRoutes(
            fn ($routes) => array_push(
                $this->results,
                ...$routesConverter->convert($routes),
            ),
        );

        $this->ranger->walk();

        $this->writeFiles();
    }

    protected function getBasePaths(): array
    {
        if ($this->option('base-path')) {
            return array_map('trim', explode(',', $this->option('base-path')));
        }

        return [base_path()];
    }

    protected function getAppPaths(): array
    {
        if ($this->option('app-path')) {
            return array_map('trim', explode(',', $this->option('app-path')));
        }

        return [app_path()];
    }

    protected function writeFiles(): void
    {
        $this->files->ensureDirectoryExists($this->generatedDirectory);
        $this->files->cleanDirectory($this->generatedDirectory);

        $validResults = array_filter($this->results);

        if (count($validResults) > 0) {
            $progress = progress('Writing files...', count($validResults));
            $progress->start();

            foreach ($validResults as $result) {
                $progress->label($result->name);
                $path = join_paths($this->generatedDirectory, $result->name);

                $this->files->ensureDirectoryExists(dirname($path));
                $this->writeFile($path, $result->content());

                $progress->advance();
            }

            $progress->label('Done!');
            $progress->render();
        }

        $namespaced = TypeScript::getNamespacedFormatted();

        if ($namespaced->isNotEmpty()) {
            info('Writing namespaced TypeScript files...');

            $this->writeFile(
                join_paths($this->generatedDirectory, 'types.d.ts'),
                $namespaced->join(PHP_EOL),
            );
        }

        // TypeScript::getNamespaced()->undot()->each($this->writeTypeBarrelFile(...));

        info('Writing barrel files...');

        foreach (Finder::create()->directories()->in($this->generatedDirectory) as $dir) {
            $this->writeBarrelFile($dir);
        }

        $this->files->copy(__DIR__.'/../../resources/js/wayfinder.ts', join_paths($this->generatedDirectory, 'index.ts'));

        if ($this->config->get('wayfinder.format.enabled', false)) {
            info('Formatting...');
            exec('npx @biomejs/biome format --write '.escapeshellarg($this->generatedDirectory).' --indent-width 4 --indent-style space > /dev/null 2>&1 &');
        }
    }

    protected function writeTypeBarrelFile($lines, $key)
    {
        $keyPath = str($key)->replace('.', DIRECTORY_SEPARATOR)->toString();
        $relativePath = join_paths('types', $keyPath, 'index.ts');
        $path = join_paths($this->generatedDirectory, $relativePath);
        $dotBase = str($key)->before('.')->toString();
        $dotEnd = str($key)->afterLast('.')->toString();

        if ($key !== $dotBase) {
            File::ensureDirectoryExists(dirname($path));

            $safeKey = collect(explode('.', $key))->map(fn ($k) => TypeScript::safeMethod($k, '_'))->implode('.');
            $safeDotEnd = TypeScript::safeMethod($dotEnd, '_');

            if (! file_exists($path)) {
                $this->writeFile($path, [
                    (string) Imports::create()->add(Import::relativePathFromFile($relativePath, 'types'), $dotBase),
                    '',
                    "import {$safeDotEnd} = {$safeKey};",
                    '',
                    "export { {$safeDotEnd} };",
                ]);
            }
        }

        if (! array_is_list($lines)) {
            foreach ($lines as $k => $line) {
                $this->writeTypeBarrelFile($line, $key.'.'.$k);
            }
        }
    }

    protected function writeBarrelFile(SplFileInfo $dir)
    {
        $isTypeDir = str_starts_with($dir->getPathname(), join_paths($this->generatedDirectory, 'types'));

        if ($isTypeDir) {
            return;
        }

        $path = join_paths($dir->getPathname(), 'index.ts');

        if ($this->files->exists($path)) {
            return;
        }

        $fileList = Finder::create()
            ->files()
            ->depth(0)
            ->filter(fn (SplFileInfo $f) => $f->getExtension() === 'ts')
            ->in($dir->getPathname());

        $dirList = Finder::create()
            ->directories()
            ->depth(0)
            ->in($dir->getPathname());

        $files = collect($fileList)->map(
            fn (SplFileInfo $file) => str($file->getBasename())
                ->replaceLast('.'.$file->getExtension(), '')
                ->toString()
        )->filter(fn ($f) => $f !== 'index');

        $dirs = collect($dirList)->map(fn (SplFileInfo $file) => $file->getBasename());

        if ($files->isEmpty() && $dirs->isEmpty()) {
            return;
        }

        $imports = new Imports;

        $object = TypeScript::object();

        foreach ($files as $f) {
            $imports->addSafeMethod("./{$f}", $f, 'Method', default: true);
            $object->key(TypeScript::safeMethod($f, 'Method'))->rawKey();
        }

        foreach ($dirs as $d) {
            $imports->addWildcard("./{$d}", TypeScript::safeMethod($d, 'Method'), default: true);
            $object->key(TypeScript::safeMethod($d, 'Method'))->rawKey();
        }

        $exports = collect($imports->asLines())
            ->push('')
            ->push((string) TypeScript::constant($dir->getBasename(), $object)->export())
            ->push('')
            ->push((string) TypeScript::block($dir->getBasename())->exportDefault())
            ->join(PHP_EOL);

        $this->writeFile($path, $exports);
    }

    protected function writeFile(string $path, string|array $content)
    {
        if (is_array($content)) {
            $content = implode(PHP_EOL, $content);
        }

        $comment = <<<'COMMENT'
        // This file is auto-generated by Laravel Wayfinder.
        // Do not edit this file directly, any changes will be overwritten.
        COMMENT;

        $this->files->put($path, $comment.PHP_EOL.PHP_EOL.$content);
    }
}
