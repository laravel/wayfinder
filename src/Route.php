<?php

namespace Laravel\Wayfinder;

use Closure;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Routing\RouteAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use ReflectionClass;

class Route
{
    public function __construct(
        private BaseRoute $base,
        private Collection $paramDefaults,
        private ?string $forcedScheme,
        private ?string $forcedRoot
    ) {
        //
    }

    public function hasController(): bool
    {
        return $this->base->getControllerClass() !== null;
    }

    public function dotNamespace(): string
    {
        return str_replace('\\', '.', Str::chopStart($this->controller(), '\\'));
    }

    public function hasInvokableController(): bool
    {
        return $this->base->getActionName() === $this->base->getActionMethod();
    }

    public function method(): string
    {
        return $this->hasInvokableController()
            ? '__invoke'
            : $this->base->getActionMethod();
    }

    public function jsMethod(): string
    {
        return $this->finalJsMethod($this->originalJsMethod());
    }

    public function originalJsMethod()
    {
        return $this->hasInvokableController()
            ? Str::afterLast($this->controller(), '\\')
            : $this->base->getActionMethod();
    }

    public function namedMethod(): string
    {
        return $this->finalJsMethod(Str::afterLast($this->name(), '.'));
    }

    public function controller(): string
    {
        return $this->hasInvokableController()
            ? Str::start($this->base->getActionName(), '\\')
            : Str::start($this->base->getControllerClass(), '\\');
    }

    public function parameters(): Collection
    {
        $optionalParameters = collect($this->base->toSymfonyRoute()->getDefaults());

        $signatureParams = collect($this->base->signatureParameters(UrlRoutable::class));

        return collect($this->base->parameterNames())->map(fn ($name) => new Parameter(
            $name,
            $optionalParameters->has($name) || $this->paramDefaults->has($name),
            $this->base->bindingFieldFor($name),
            $this->paramDefaults->get($name),
            $signatureParams->first(fn ($p) => $p->getName() === $name),
        ));
    }

    public function verbs(): Collection
    {
        return collect($this->base->methods())->mapInto(Verb::class);
    }

    public function uri(): string
    {
        $defaultParams = $this->paramDefaults->mapWithKeys(fn ($value, $key) => ["{{$key}}" => "{{$key}?}"]);

        $scheme = $this->scheme() ?? '//';

        $uri = str($this->base->uri)
            ->start('/')
            ->when($this->domain() !== null, fn ($uri) => $uri->prepend("{$scheme}{$this->domain()}"))
            ->replace($defaultParams->keys()->toArray(), $defaultParams->values()->toArray())
            ->toString();

        return Js::from($uri, JSON_UNESCAPED_SLASHES)->toHtml();
    }

    public function scheme(): ?string
    {
        if ($this->base->httpOnly()) {
            return 'http://';
        }

        if ($this->base->httpsOnly()) {
            return 'https://';
        }

        return $this->forcedScheme;
    }

    public function domain(): ?string
    {
        return $this->base->getDomain() ?? $this->forcedRoot;
    }

    public function name(): ?string
    {
        $name = $this->base->getName();

        if (! $name || Str::endsWith($name, '.') || Str::startsWith($name, 'generated::')) {
            return null;
        }

        if (str_contains($name, '::')) {
            return 'namespaced.'.str_replace('::', '.', $name);
        }

        return $name;
    }

    public function controllerPath(): string
    {
        $controller = $this->controller();

        if ($controller === '\\Closure') {
            $path = $this->relativePath((new ReflectionClosure($this->closure()))->getFileName());

            if (str_contains($path, 'laravel-serializable-closure')) {
                return '[serialized-closure]';
            }

            return $path;
        }

        if (! class_exists($controller)) {
            return '[unknown]';
        }

        return $this->relativePath((new ReflectionClass($controller))->getFileName());
    }

    public function controllerMethodLineNumber(): int
    {
        $controller = $this->controller();

        if ($controller === '\\Closure') {
            return (new ReflectionClosure($this->closure()))->getStartLine();
        }

        if (! class_exists($controller)) {
            return 0;
        }

        $reflection = (new ReflectionClass($controller));

        if ($reflection->hasMethod($this->method())) {
            return $reflection->getMethod($this->method())->getStartLine();
        }

        return 0;
    }

    private function finalJsMethod(string $method): string
    {
        return TypeScript::safeMethod($method, 'Method');
    }

    private function relativePath(string $path)
    {
        return str($path)->replace(base_path(), '')->ltrim(DIRECTORY_SEPARATOR)->replace(DIRECTORY_SEPARATOR, '/')->toString();
    }

    private function closure(): Closure
    {
        return RouteAction::containsSerializedClosure($this->base->getAction())
            ? unserialize($this->base->getAction('uses'))->getClosure()
            : $this->base->getAction('uses');
    }
}
