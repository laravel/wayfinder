<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Langs\TypeScript\Converters\RouteMethod;
use Laravel\Wayfinder\Langs\TypeScript\Import;
use Laravel\Wayfinder\Langs\TypeScript\Imports;
use Laravel\Wayfinder\Results\Result;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Laravel\Ranger\Components\InertiaResponse;
use Laravel\Ranger\Components\JsonResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Support\RouteParameter;
use Laravel\Ranger\Support\Verb;

use function Illuminate\Filesystem\join_paths;

class Routes extends Converter
{
    protected string $baseDir = 'routes';

    protected array $imports = [];

    protected array $exports = [];

    protected array $content = [];

    protected bool $generateRoutes = true;

    protected bool $generateActions = true;

    protected bool $withForm = true;

    public function __construct(
        protected InertiaData $inertiaDataConverter,
        protected JsonData $jsonDataConverter,
        protected FormRequests $formRequestConverter,
        protected Repository $config,
    ) {
        //
    }

    /**
     * @param  Collection<Route>  $routes
     */
    public function convert(Collection $routes): array
    {
        $results = [];

        if ($this->generateActions) {
            $controllers = $routes->filter(fn(Route $route) => $route->hasController())
                ->groupBy(fn(Route $route) => $route->dotNamespace());

            $controllers->each($this->writeControllerFile(...));
        }

        if ($this->generateRoutes) {
            $this->writeNamedRoutesFile($routes);
        }

        $routes->unique(fn(Route $route) => $route->controller() . ':' . $route->method())->each(function (Route $route) {
            $responseTypes = [];

            foreach ($route->possibleResponses() as $response) {
                if ($response instanceof InertiaResponse) {
                    $responseTypes[] = $this->inertiaDataConverter->convert($response, $route);
                }

                if ($response instanceof JsonResponse) {
                    $responseTypes[] = $this->jsonDataConverter->convert($response, $route);
                }
            }

            $responseTypes = array_filter($responseTypes);

            if (count($responseTypes) > 0) {
                TypeScript::addFqnToNamespaced(
                    [$route->controller(), ucwords($route->method()), 'Response'],
                    TypeScript::type('Response', implode(' | ', $responseTypes))->export(),
                )->referenceMethod(
                    $route->controller(),
                    $route->method(),
                    $route->controllerPath(),
                );
            }

            $this->formRequestConverter->add($route->requestValidator(), $route);
        });

        $this->formRequestConverter->convert();

        foreach ($this->content as $path => $content) {
            $resultImports = Imports::create();

            if (isset($this->imports[$path])) {
                foreach ($this->imports[$path]->imports as $imports) {
                    $resultImports->addImports($imports);
                }
            }

            if (isset($this->exports[$path])) {
                $defaultExportedVarName = TypeScript::safeMethod(str($path)->beforeLast(DIRECTORY_SEPARATOR)->afterLast(DIRECTORY_SEPARATOR)->toString(), 'Object');
                $dir = dirname($path);
                $object = TypeScript::object();

                foreach ($this->exports[$path] as $export) {
                    if ($export['safeMethod'] !== $export['originalMethod']) {
                        $object->key($export['originalMethod'])->value($export['safeMethod']);
                    } else {
                        $object->key($export['originalMethod'])->rawKey();
                    }
                }

                foreach ($this->exports as $exportPath => $exports) {
                    if ($dir === $this->baseDir || $exportPath === $path || ! str_starts_with($exportPath, $dir)) {
                        continue;
                    }

                    $firstSubDir = str($exportPath)->after($dir)->trim(DIRECTORY_SEPARATOR)->before(DIRECTORY_SEPARATOR)->toString();

                    if ($exportPath !== join_paths($dir, $firstSubDir, 'index.ts')) {
                        continue;
                    }

                    $inFile = in_array($firstSubDir, array_column($this->exports[$path], 'originalMethod'));

                    $resultImports->addDefault('./' . $firstSubDir, TypeScript::safeMethod($firstSubDir, 'Method'), safe: $inFile);

                    $keyValue = $object->key(TypeScript::safeMethod($firstSubDir, 'Method'));

                    if ($inFile) {
                        $keyValue->value(sprintf('Object.assign(%s, %s)', $firstSubDir, $resultImports->get($firstSubDir)));
                    } else {
                        $keyValue->rawKey();
                    }
                }

                $content[] = '';
                $content[] = (string) TypeScript::constant($defaultExportedVarName, $object);
                $content[] = '';
                $content[] = (string) TypeScript::block($defaultExportedVarName)->exportDefault();
            }

            $results[] = new Result($path, implode(PHP_EOL, $content), $resultImports);
        }

        return $results;
    }

    public function withForm(bool $withForm): void
    {
        $this->withForm = $withForm;
    }

    public function generateFormVariants(): bool
    {
        return $this->withForm;
    }

    public function generateRoutes(bool $generateRoutes): void
    {
        $this->generateRoutes = $generateRoutes;
    }

    public function generateActions(bool $generateActions): void
    {
        $this->generateActions = $generateActions;
    }

    protected function writeNamedRoutesFile(Collection $routes): void
    {
        $routes
            ->filter(fn(Route $route) => $route->name())
            ->groupBy(fn(Route $route) => str_contains($route->name(), '.') ? str($route->name())->beforeLast('.')->toString() : '')
            ->each($this->writeNamedFile(...));
    }

    protected function writeNamedFile(Collection $routes, string $namespace): void
    {
        $path = join_paths($this->baseDir, str_replace('.', DIRECTORY_SEPARATOR, $namespace), 'index') . '.ts';

        $this->appendCommonImports($routes, $path);

        $routes->map(fn(Route $route) => $this->writeNamedMethodExport($route, $path));
    }

    protected function writeControllerFile(Collection $routes, string $namespace): void
    {
        $path = str_replace('.', DIRECTORY_SEPARATOR, $namespace) . '.ts';

        $this->appendCommonImports($routes, $path);

        $results = $routes->groupBy(fn(Route $route) => $route->method())->map(
            fn($methodRoutes) => ($methodRoutes->count() === 1)
                ? $this->writeControllerMethodExport($methodRoutes->first(), $path)
                : $this->writeMultiRouteControllerMethodExport($methodRoutes, $path),
        );

        $hasInvokable = $routes->first(fn(Route $route) => $route->hasInvokableController());

        $exports = [];

        $lastNamespace = last(explode('.', $namespace));

        if ($hasInvokable) {
            foreach ($results as $result) {
                foreach ($result->computedMethods() as $method => $name) {
                    if ($method !== '__invoke') {
                        $exports[] = "{$lastNamespace}.{$method} = {$name}";
                    }
                }
            }
        } else {
            $object = TypeScript::object();

            foreach ($results as $result) {
                foreach ($result->computedMethods() as $method => $name) {
                    $object->key($method)->value($name);
                }
            }

            $exports[] = TypeScript::constant($lastNamespace, $object);
        }

        $exports[] = TypeScript::block($lastNamespace)->exportDefault();

        $this->appendContent($path, PHP_EOL . implode(PHP_EOL . PHP_EOL, $exports));
    }

    protected function appendContent($path, $content): void
    {
        $this->content[$path] ??= [];

        if (! in_array($content, $this->content[$path])) {
            $this->content[$path][] = $content;
        }
    }

    protected function writeControllerMethodExport(Route $route, string $path): RouteMethod
    {
        $method = new RouteMethod($route, $this->generateFormVariants());

        $this->appendContent($path, $method->controllerMethod());

        return $method;
    }

    protected function writeNamedMethodExport(Route $route, string $path): RouteMethod
    {
        $method = new RouteMethod($route, $this->generateFormVariants(), true);

        $this->appendContent($path, $method->controllerMethod());

        $this->exports[$path] ??= [];

        foreach ($method->computedMethods() as $routeMethod => $name) {
            $this->exports[$path][] = [
                'originalMethod' => $routeMethod === '__invoke' ? $name : $routeMethod,
                'safeMethod' => $name,
            ];
        }

        return $method;
    }

    protected function writeMultiRouteControllerMethodExport(Collection $routes, string $path): RouteMethod
    {
        $method = new RouteMethod(
            route: $routes->first(),
            withForm: $this->generateFormVariants(),
            relatedRoutes: $routes->all(),
        );

        $this->appendContent($path, $method->controllerMethod());

        return $method;
    }

    protected function appendCommonImports(Collection $routes, string $path): void
    {
        $pathKey = Import::relativePathFromFile($path, 'index');

        $this->imports[$path] ??= new Imports;

        $this->imports[$path]->add($pathKey, 'queryParams');
        $this->imports[$path]->addType($pathKey, 'RouteQueryOptions');
        $this->imports[$path]->addType($pathKey, 'RouteDefinition');

        if ($this->generateFormVariants()) {
            $this->imports[$path]->addType($pathKey, 'RouteFormDefinition');

            if ($routes->first(fn(Route $route) => $route->verbs()->first(fn(Verb $verb) => $verb->formSafe !== $verb->actual))) {
                $this->imports[$path]->add($pathKey, 'formSafeOptions');
            }
        }

        if ($routes->first(fn(Route $route) => $route->parameters()->first(fn(RouteParameter $parameter) => $parameter->optional))) {
            $this->imports[$path]->add($pathKey, 'validateParameters');
        }

        if ($routes->first(fn(Route $route) => $route->parameters()->isNotEmpty())) {
            $this->imports[$path]->add($pathKey, 'applyUrlDefaults');
        }
    }
}
