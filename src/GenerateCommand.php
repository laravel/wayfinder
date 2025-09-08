<?php

namespace Laravel\Wayfinder;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use ReflectionProperty;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\info;

class GenerateCommand extends Command
{
    protected $signature = 'wayfinder:generate {--path=} {--skip-actions} {--skip-routes} {--with-form}';

    private ?string $forcedScheme;

    private ?string $forcedRoot;

    private $urlDefaults = [];

    private $pathDirectory = 'actions';

    private $content = [];

    /**
     * Imports array where the key is the generated file path and the value is an array of imports.
     * Each import is an array where the first element is the import path and the second element is an array of imported items.
     *
     * @var array<string, array<string, string[]>>
     */
    private $imports = [];

    public function __construct(
        private Filesystem $files,
        private Router $router,
        private Factory $view,
        private UrlGenerator $url,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->view->addNamespace('wayfinder', __DIR__.'/../resources');
        $this->view->addExtension('blade.ts', 'blade');

        $this->forcedScheme = (new ReflectionProperty($this->url, 'forceScheme'))->getValue($this->url);
        $this->forcedRoot = (new ReflectionProperty($this->url, 'forcedRoot'))->getValue($this->url);

        $globalUrlDefaults = collect(URL::getDefaultParameters())->map(fn ($v) => is_scalar($v) || is_null($v) ? $v : '');

        $routes = collect($this->router->getRoutes())->map(function (BaseRoute $route) use ($globalUrlDefaults) {
            $defaults = collect($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
                if ($middleware instanceof \Closure) {
                    return [];
                }

                $this->urlDefaults[$middleware] ??= $this->getDefaultsForMiddleware($middleware);

                return $this->urlDefaults[$middleware];
            })->flatMap(fn ($r) => $r);

            return new Route($route, $globalUrlDefaults->merge($defaults), $this->forcedScheme, $this->forcedRoot);
        });

        if (! $this->option('skip-actions')) {
            $this->files->deleteDirectory($this->base());

            $controllers = $routes->filter(fn (Route $route) => $route->hasController())->groupBy(fn (Route $route) => $route->dotNamespace());

            $controllers->undot()->each($this->writeBarrelFiles(...));
            $controllers->each($this->writeControllerFile(...));

            $this->writeContent();

            info('[Wayfinder] Generated actions in '.$this->base());
        }

        $this->pathDirectory = 'routes';

        if (! $this->option('skip-routes')) {
            $this->files->deleteDirectory($this->base());

            $named = $routes->filter(fn (Route $route) => $route->name())->groupBy(fn (Route $route) => $route->name());

            $named->each($this->writeNamedFile(...));
            $named->undot()->each($this->writeBarrelFiles(...));

            $this->writeContent();

            info('[Wayfinder] Generated routes in '.$this->base());
        }

        $this->pathDirectory = 'wayfinder';

        $this->files->ensureDirectoryExists($this->base());
        $this->files->copy(__DIR__.'/../resources/js/wayfinder.ts', join_paths($this->base(), 'index.ts'));
    }

    private function appendContent($path, $content): void
    {
        $this->content[$path] ??= [];

        if (! in_array($content, $this->content[$path])) {
            $this->content[$path][] = $content;
        }
    }

    private function prependContent($path, $content): void
    {
        $this->content[$path] ??= [];

        array_unshift($this->content[$path], $content);
    }

    private function writeContent(): void
    {
        foreach ($this->content as $path => $content) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, TypeScript::cleanUp(implode(PHP_EOL, $content)));

            // Prepend the imports to the file
            if (isset($this->imports[$path])) {
                $importLines = collect($this->imports[$path])->map(fn ($imports, $key) => 'import { '.implode(', ', array_unique($imports))." } from '{$key}'")->implode(PHP_EOL);
                $this->files->prepend($path, $importLines.PHP_EOL);
            }
        }

        $this->content = [];
    }

    private function writeControllerFile(Collection $routes, string $namespace): void
    {
        $path = join_paths($this->base(), ...explode('.', $namespace)).'.ts';

        $this->appendCommonImports($routes, $path, $namespace);

        $routes->groupBy(fn (Route $route) => $route->method())->each(function ($methodRoutes) use ($path) {
            if ($methodRoutes->count() === 1) {
                return $this->writeControllerMethodExport($methodRoutes->first(), $path);
            }

            return $this->writeMultiRouteControllerMethodExport($methodRoutes, $path);
        });

        [$invokable, $methods] = $routes->partition(fn (Route $route) => $route->hasInvokableController());

        $defaultExport = $invokable->isNotEmpty() ? $invokable->first()->jsMethod() : last(explode('.', $namespace));

        if ($invokable->isEmpty()) {
            $exportedMethods = $methods->map(fn (Route $route) => $route->jsMethod());
            $reservedMethods = $methods->filter(fn (Route $route) => $route->originalJsMethod() !== $route->jsMethod())->map(fn (Route $route) => TypeScript::quoteIfNeeded($route->originalJsMethod()).': '.$route->jsMethod());
            $exportedMethods = $exportedMethods->merge($reservedMethods);

            $methodProps = "const {$defaultExport} = { ";
            $methodProps .= $exportedMethods->unique()->implode(', ');
            $methodProps .= ' }';
        } else {
            $methodProps = $methods->map(fn (Route $route) => $defaultExport.'.'.$route->jsMethod().' = '.$route->jsMethod())->unique()->implode(PHP_EOL);
        }

        $this->appendContent($path, <<<JAVASCRIPT
        {$methodProps}

        export default {$defaultExport}
        JAVASCRIPT);
    }

    private function writeMultiRouteControllerMethodExport(Collection $routes, string $path): void
    {
        $isInvokable = $routes->first()->hasInvokableController();

        $this->appendContent($path, $this->view->make('wayfinder::multi-method', [
            'method' => $routes->first()->jsMethod(),
            'original_method' => $routes->first()->originalJsMethod(),
            'path' => $routes->first()->controllerPath(),
            'line' => $routes->first()->controllerMethodLineNumber(),
            'controller' => $routes->first()->controller(),
            'isInvokable' => $isInvokable,
            'shouldExport' => ! $isInvokable,
            'withForm' => $this->option('with-form') ?? false,
            'routes' => $routes->map(fn ($r) => [
                'method' => $r->jsMethod(),
                'tempMethod' => $r->jsMethod().hash('xxh128', $r->uri()),
                'parameters' => $r->parameters(),
                'verbs' => $r->verbs(),
                'uri' => $r->uri(),
            ]),
        ]));
    }

    private function writeControllerMethodExport(Route $route, string $path): void
    {
        $this->appendContent($path, $this->view->make('wayfinder::method', [
            'controller' => $route->controller(),
            'method' => $route->jsMethod(),
            'original_method' => $route->originalJsMethod(),
            'isInvokable' => $route->hasInvokableController(),
            'shouldExport' => ! $route->hasInvokableController(),
            'path' => $route->controllerPath(),
            'line' => $route->controllerMethodLineNumber(),
            'parameters' => $route->parameters(),
            'verbs' => $route->verbs(),
            'uri' => $route->uri(),
            'withForm' => $this->option('with-form') ?? false,
        ]));
    }

    private function writeNamedFile(Collection $routes, string $namespace): void
    {
        $parts = explode('.', $namespace);
        array_pop($parts);
        $parts[] = 'index';

        $path = join_paths($this->base(), ...$parts).'.ts';

        $this->appendCommonImports($routes, $path, $namespace);

        $routes->each(fn (Route $route) => $this->writeNamedMethodExport($route, $path));
    }

    private function appendCommonImports(Collection $routes, string $path, string $namespace): void
    {
        $imports = ['queryParams', 'type RouteQueryOptions', 'type RouteDefinition'];

        if ($this->option('with-form') === true) {
            $imports[] = 'type RouteFormDefinition';
        }

        if ($routes->contains(fn (Route $route) => $route->parameters()->isNotEmpty())) {
            $imports[] = 'applyUrlDefaults';
        }

        if ($routes->contains(fn (Route $route) => $route->parameters()->contains(fn (Parameter $parameter) => $parameter->optional))) {
            $imports[] = 'validateParameters';
        }

        $importBase = str_repeat('/..', substr_count($namespace, '.') + 1);
        $pathKey = ".{$importBase}/wayfinder";

        $this->imports[$path] ??= [];
        $this->imports[$path][$pathKey] = [
            ...($this->imports[$path][$pathKey] ?? []),
            ...$imports,
        ];
    }

    private function writeNamedMethodExport(Route $route, string $path): void
    {
        $this->appendContent($path, $this->view->make('wayfinder::method', [
            'controller' => $route->controller(),
            'method' => $route->namedMethod(),
            'original_method' => $route->originalJsMethod(),
            'isInvokable' => $route->hasInvokableController(),
            'shouldExport' => true,
            'path' => $route->controllerPath(),
            'line' => $route->controllerMethodLineNumber(),
            'parameters' => $route->parameters(),
            'verbs' => $route->verbs(),
            'uri' => $route->uri(),
            'withForm' => $this->option('with-form') ?? false,
        ]));
    }

    private function writeBarrelFiles(array|Collection $children, string $parent): void
    {
        $children = collect($children);

        if (array_is_list($children->all())) {
            return;
        }

        $indexPath = join_paths($this->base(), $parent, 'index.ts');
        $keysWithGrandkids = $children->filter(fn ($grandChildren) => ! array_is_list(collect($grandChildren)->all()));

        $childKeys = $children->keys()->mapWithKeys(function ($child) use ($indexPath, $keysWithGrandkids) {
            $safeMethod = TypeScript::safeMethod($child, 'Method');
            $safe = $safeMethod;

            if ($keysWithGrandkids->has($child)) {
                foreach ($this->content[$indexPath] ?? [] as $content) {
                    if (str_contains((string) $content, 'const '.$safeMethod.' =')) {
                        $safe .= str(hash('xxh128', $safe))->substr(0, 6)->ucfirst();
                    }
                }
            }

            return [
                $child => [
                    'safe' => $safe,
                    'safeAssign' => "Object.assign({$safeMethod}, {$safe})",
                    'normalized' => str($child)->whenContains('-', fn ($s) => $s->camel())->toString(),
                ],
            ];
        });

        if (! ($this->content[$indexPath] ?? false)) {
            $imports = $childKeys->filter(fn ($_, $key) => $key !== 'index')->map(fn ($alias, $key) => "import {$alias['safe']} from './{$key}'")->implode(PHP_EOL);
        } else {
            $imports = $childKeys->only($keysWithGrandkids->keys())->map(fn ($alias, $key) => "import {$alias['safe']} from './{$key}'")->implode(PHP_EOL);
        }

        if ($imports) {
            $this->prependContent($indexPath, $imports);
        }

        $keys = $childKeys->map(fn ($alias, $key) => str_repeat(' ', 4).implode(': ', array_unique([$alias['normalized'], $alias['safeAssign'] ?? $alias['safe']])))->implode(', '.PHP_EOL);

        $varExport = TypeScript::safeMethod(Str::afterLast($parent, DIRECTORY_SEPARATOR), 'Method');

        $this->appendContent($indexPath, <<<JAVASCRIPT


                const {$varExport} = {
                {$keys},
                }

                export default {$varExport}
                JAVASCRIPT);

        $children->each(fn ($grandChildren, $child) => $this->writeBarrelFiles($grandChildren, join_paths($parent, $child)));
    }

    private function base(): string
    {
        $base = $this->option('path') ?? join_paths(resource_path(), 'js');

        return join_paths($base, $this->pathDirectory);
    }

    private function getDefaultsForMiddleware(string $middleware)
    {
        if (! class_exists($middleware)) {
            return [];
        }

        $reflection = new \ReflectionClass($middleware);

        if (! $reflection->hasMethod('handle')) {
            return [];
        }

        $method = $reflection->getMethod('handle');

        // Get the file name and line numbers
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        // Read the file and extract the method contents
        $lines = file($fileName);
        $methodContents = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        if (! str_contains($methodContents, 'URL::defaults')) {
            return [];
        }

        $methodContents = str($methodContents)->after('{')->beforeLast('}')->trim();

        return $this->extractUrlDefaults($methodContents);
    }

    private function extractUrlDefaults(string $methodContents): array
    {
        $tokens = token_get_all('<?php '.$methodContents);
        $foundUrlFacade = false;
        $defaults = [];
        $inArray = false;

        foreach ($tokens as $index => $token) {
            if (is_array($token) && token_name($token[0]) === 'T_STRING') {
                if (
                    $token[1] === 'URL'
                    && is_array($tokens[$index + 1])
                    && $tokens[$index + 1][1] === '::'
                    && is_array($tokens[$index + 2])
                    && $tokens[$index + 2][1] === 'defaults'
                ) {
                    $foundUrlFacade = true;
                }
            }

            if (! $foundUrlFacade) {
                continue;
            }

            if ((is_array($token) && $token[0] === T_ARRAY) || $token === '[') {
                $inArray = true;
            }

            // If we are in an array context and the token is a string (key)
            if (! $inArray) {
                continue;
            }

            if (is_array($token) && $token[0] === T_DOUBLE_ARROW) {
                $count = 1;
                $previousToken = $tokens[$index - $count];

                // Work backwards to get the key
                while (is_array($previousToken) && $previousToken[0] === T_WHITESPACE) {
                    $count++;
                    $previousToken = $tokens[$index - $count];
                }

                $valueToken = $tokens[$index + 1];
                $count = 1;

                // Work backwards to get the key
                while (is_array($valueToken) && $valueToken[0] === T_WHITESPACE) {
                    $count++;
                    $valueToken = $tokens[$index + $count];
                }

                $value = trim($valueToken[1], "'\"");

                $value = match ($value) {
                    'true' => 1,
                    'false' => 0,
                    default => $value,
                };

                $defaults[trim($previousToken[1], "'\"")] = $value;
            }

            // Check for the closing bracket of the array
            if ($token === ']') {
                $inArray = false;
                break;
            }
        }

        return $defaults;
    }
}
