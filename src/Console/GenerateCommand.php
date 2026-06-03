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
    protected $signature = 'wayfinder:generate {--path=} {--base-path=} {--app-path=} {--fresh} {--explain : Display generation diagnostics after writing files}';

    protected $description = 'Generate TypeScript files for your Laravel application';

    protected string $generatedDirectory;

    /**
     * @var Result[]
     */
    protected $results = [];

    protected array $routeDiagnostics = [
        'routes' => 0,
        'named_routes' => 0,
        'controller_routes' => 0,
        'controller_namespaces' => 0,
        'form_request_routes' => 0,
    ];

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
        $routesConverter->withInertiaComponent($this->config->get('wayfinder.generate.inertia.component', false));
        RangerConfig::set('routes.ignore_names', $this->config->get('wayfinder.generate.route.ignore.names', []));
        RangerConfig::set('routes.ignore_urls', $this->config->get('wayfinder.generate.route.ignore.urls', []));

        $this->ranger->onRoutes(
            function ($routes) use ($routesConverter) {
                $this->captureRouteDiagnostics($routes);
                array_push(
                    $this->results,
                    ...$routesConverter->convert($routes),
                );
            },
        );

        $this->ranger->walk();

        $this->writeFiles();

        if ($this->option('explain')) {
            $this->renderExplanation($basePaths, $appPaths);
        }
    }

    protected function captureRouteDiagnostics($routes): void
    {
        $this->routeDiagnostics = [
            'routes' => $routes->count(),
            'named_routes' => $routes->filter(fn ($route) => $route->name())->count(),
            'controller_routes' => $routes->filter(fn ($route) => $route->hasController())->count(),
            'controller_namespaces' => $routes->filter(fn ($route) => $route->hasController())->map(fn ($route) => $route->dotNamespace())->unique()->count(),
            'form_request_routes' => $routes->filter(fn ($route) => $route->requestValidator() !== null)->count(),
        ];
    }

    protected function renderExplanation(array $basePaths, array $appPaths): void
    {
        $this->newLine();
        $this->line('<info>Wayfinder explain</info>');

        $this->renderExplanationSection('Paths', [
            'Output path' => $this->displayPath($this->generatedDirectory),
            'Base paths' => $this->displayPaths($basePaths),
            'App paths' => $this->displayPaths($appPaths),
        ]);

        $this->renderExplanationSection('Configuration', [
            'Cache' => $this->cacheExplanation(),
            'Format generated files' => $this->enabled($this->config->get('wayfinder.format.enabled', false)),
            'Route ignore names' => $this->formatList($this->config->get('wayfinder.generate.route.ignore.names', [])),
            'Route ignore URLs' => $this->formatList($this->config->get('wayfinder.generate.route.ignore.urls', [])),
        ]);

        $this->renderExplanationSection('Generators', [
            'Controller actions' => $this->enabled($this->config->get('wayfinder.generate.route.actions', true)),
            'Named routes' => $this->enabled($this->config->get('wayfinder.generate.route.named', true)),
            'Form variants' => $this->enabled($this->config->get('wayfinder.generate.route.form_variant', true)),
            'Models' => $this->enabled($this->config->get('wayfinder.generate.models', true)),
            'Inertia shared data' => $this->enabled($this->config->get('wayfinder.generate.inertia.shared_data', true)),
            'Inertia components' => $this->enabled($this->config->get('wayfinder.generate.inertia.component', false)),
            'Broadcast channels' => $this->enabled($this->config->get('wayfinder.generate.broadcast.channels', true)),
            'Broadcast events' => $this->enabled($this->config->get('wayfinder.generate.broadcast.events', true)),
            'Environment variables' => $this->enabled($this->config->get('wayfinder.generate.environment_variables', true)),
            'Enums' => $this->enabled($this->config->get('wayfinder.generate.enums', true)),
        ]);

        $this->renderExplanationSection('Analyzed', [
            'Routes' => $this->routeDiagnostics['routes'],
            'Named routes' => $this->routeDiagnostics['named_routes'],
            'Controller routes' => $this->routeDiagnostics['controller_routes'],
            'Controller namespaces' => $this->routeDiagnostics['controller_namespaces'],
            'Form request routes' => $this->routeDiagnostics['form_request_routes'],
        ]);

        $this->renderExplanationSection('Output', [
            'Result files' => count(array_filter($this->results)),
            'Type namespaces' => TypeScript::getNamespaced()->count(),
            'Generated files' => $this->generatedFileCount(),
        ]);

        $this->renderExplanationWarnings();
    }

    protected function renderExplanationSection(string $title, array $items): void
    {
        $this->newLine();
        $this->line('<comment>'.$title.'</comment>');

        foreach ($items as $label => $value) {
            $this->line(sprintf('  %-24s %s', $label.':', $value));
        }
    }

    protected function renderExplanationWarnings(): void
    {
        $warnings = [];

        if (method_exists(app(), 'routesAreCached') && app()->routesAreCached()) {
            $warnings[] = 'Routes are cached. Run route:clear before generating if routes changed in this release.';
        }

        if ($this->generatedFileCount() === 0) {
            $warnings[] = 'No files were generated. Check the enabled generators and scanned paths.';
        }

        if (empty($warnings)) {
            return;
        }

        $this->newLine();
        $this->line('<comment>Warnings</comment>');

        foreach ($warnings as $warning) {
            $this->line('  - '.$warning);
        }
    }

    protected function cacheExplanation(): string
    {
        if ($this->option('fresh')) {
            return 'cleared for this run';
        }

        return $this->enabled($this->config->get('wayfinder.cache.enabled', true));
    }

    protected function generatedFileCount(): int
    {
        if (! $this->files->isDirectory($this->generatedDirectory)) {
            return 0;
        }

        return count($this->files->allFiles($this->generatedDirectory));
    }

    protected function displayPaths(array $paths): string
    {
        return collect($paths)
            ->map(fn ($path) => $this->displayPath($path))
            ->implode(', ');
    }

    protected function displayPath(string $path): string
    {
        $basePath = base_path();

        if (str_starts_with($path, $basePath.DIRECTORY_SEPARATOR)) {
            return str($path)->after($basePath.DIRECTORY_SEPARATOR)->toString();
        }

        return $path;
    }

    protected function enabled(bool $enabled): string
    {
        return $enabled ? 'enabled' : 'disabled';
    }

    protected function formatList(array $items): string
    {
        return count($items) > 0 ? implode(', ', $items) : 'none';
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

        $writtenPaths = [];

        // Copy the wayfinder index first so dependent files always have a
        // resolvable target for `import { queryParams } from '.../index'`.
        // Skip the copy when the destination already matches: copy() truncates
        // before writing, briefly leaving an empty file that watchers can read.
        $indexSource = __DIR__.'/../../resources/js/wayfinder.ts';
        $indexDest = join_paths($this->generatedDirectory, 'index.ts');
        $indexBody = $this->files->get($indexSource);

        if (! $this->files->exists($indexDest) || $this->files->get($indexDest) !== $indexBody) {
            $this->files->put($indexDest, $indexBody);
        }

        $writtenPaths[] = $indexDest;

        $validResults = array_filter($this->results);

        if (count($validResults) > 0) {
            $progress = progress('Writing files...', count($validResults));
            $progress->start();

            foreach ($validResults as $result) {
                $progress->label($result->name);
                $path = join_paths($this->generatedDirectory, $result->name);

                $this->files->ensureDirectoryExists(dirname($path));
                $this->writeFile($path, $result->content());
                $writtenPaths[] = $path;

                $progress->advance();
            }

            $progress->label('Done!');
            $progress->render();
        }

        $namespaced = TypeScript::getNamespacedFormatted();

        if ($namespaced->isNotEmpty()) {
            info('Writing namespaced TypeScript files...');

            $typesPath = join_paths($this->generatedDirectory, 'types.d.ts');

            $this->writeFile($typesPath, $namespaced->join(PHP_EOL));
            $writtenPaths[] = $typesPath;
        }

        // TypeScript::getNamespaced()->undot()->each($this->writeTypeBarrelFile(...));

        // Snapshot paths written by non-barrel writers (helper, results,
        // types.d.ts). Passed to writeBarrelFile() below so a computed barrel
        // can't clobber a result file that happens to be named index.ts.
        $nonBarrelPaths = $writtenPaths;

        // Preserve existing barrels in active subdirectories through the prune
        // step. writeFile() already content-skips identical writes, but if
        // prune deleted the barrel first, writeFile() would see no existing
        // file and write fresh — Vite's watcher would observe a delete +
        // create pair even when the barrel ultimately matches.
        $activeDirs = collect($nonBarrelPaths)
            ->flatMap(fn ($path) => $this->ancestorDirs($path))
            ->unique();

        foreach ($activeDirs as $dir) {
            $barrelPath = join_paths($dir, 'index.ts');

            if ($this->files->exists($barrelPath)) {
                $writtenPaths[] = $barrelPath;
            }
        }

        // Prune leftover files from previous runs before the barrel walk so it
        // doesn't see stale subdirectories. Replaces the upfront
        // cleanDirectory() that caused Vite to surface "Failed to load url" /
        // "queryParams is not defined" errors when its watcher saw the entire
        // output dir disappear and reappear on every regeneration.
        $this->pruneStaleFiles($this->generatedDirectory, $writtenPaths);

        info('Writing barrel files...');

        foreach (Finder::create()->directories()->in($this->generatedDirectory) as $dir) {
            $this->writeBarrelFile($dir, $nonBarrelPaths);
        }

        if ($this->config->get('wayfinder.format.enabled', false)) {
            info('Formatting...');
            exec('npx @biomejs/biome format --write '.escapeshellarg($this->generatedDirectory).' --indent-width 4 --indent-style space > /dev/null 2>&1 &');
        }
    }

    protected function ancestorDirs(string $path): array
    {
        $dirs = [];
        $current = dirname($path);

        while (str_starts_with($current, $this->generatedDirectory.DIRECTORY_SEPARATOR)) {
            $dirs[] = $current;
            $current = dirname($current);
        }

        return $dirs;
    }

    protected function pruneStaleFiles(string $base, array $writtenPaths): void
    {
        if (! $this->files->isDirectory($base)) {
            return;
        }

        $kept = collect($writtenPaths)
            ->map(fn ($path) => realpath($path) ?: $path)
            ->flip();

        foreach ($this->files->allFiles($base) as $file) {
            $path = $file->getPathname();

            if (! $kept->has(realpath($path) ?: $path)) {
                $this->files->delete($path);
            }
        }

        $this->pruneEmptyDirectories($base);
    }

    protected function pruneEmptyDirectories(string $dir): void
    {
        if (! $this->files->isDirectory($dir)) {
            return;
        }

        foreach ($this->files->directories($dir) as $sub) {
            $this->pruneEmptyDirectories($sub);
        }

        if (empty($this->files->files($dir)) && empty($this->files->directories($dir))) {
            $this->files->deleteDirectory($dir);
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

    protected function writeBarrelFile(SplFileInfo $dir, array $nonBarrelPaths = [])
    {
        $isTypeDir = str_starts_with($dir->getPathname(), join_paths($this->generatedDirectory, 'types'));

        if ($isTypeDir) {
            return;
        }

        $path = join_paths($dir->getPathname(), 'index.ts');

        // A non-barrel write already claimed this path; don't clobber it.
        if (in_array($path, $nonBarrelPaths)) {
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

        $allImportNames = $files->merge($dirs)->map(fn ($name) => TypeScript::safeMethod($name, 'Method'));
        $exportName = TypeScript::uniqueNamespace(TypeScript::safeMethod($dir->getBasename(), 'Object'), $allImportNames->toArray());

        $exports = collect($imports->asLines())
            ->push('')
            ->push((string) TypeScript::constant($exportName, $object)->export())
            ->push('')
            ->push((string) TypeScript::block($exportName)->exportDefault())
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

        $body = $comment.PHP_EOL.PHP_EOL.$content;

        if (! $this->files->exists($path) || $this->files->get($path) !== $body) {
            $this->files->put($path, $body);
        }
    }
}
