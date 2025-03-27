<?php

namespace TiMacDonald\Wayfinder;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Support\Collection;
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
        return $this->finalJsMethod(
            $this->hasInvokableController()
                ? Str::afterLast($this->controller(), '\\')
                : $this->base->getActionMethod()
        );
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

        return str("/{$this->base->uri}")
            ->when($this->domain() !== null, fn ($uri) => $uri->prepend("{$scheme}{$this->domain()}"))
            ->replace($defaultParams->keys()->toArray(), $defaultParams->values()->toArray())
            ->toString();
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
        return $this->base->getName();
    }

    public function controllerPath(): string
    {
        $controller = $this->controller();

        if ($controller === '\\Closure') {
            return $this->relativePath((new ReflectionClosure($this->base->getAction()['uses']))->getFileName());
        }

        return $this->relativePath((new ReflectionClass($controller))->getFileName());
    }

    public function controllerMethodLineNumber(): int
    {
        $controller = $this->controller();

        if ($controller === '\\Closure') {
            return (new ReflectionClosure($this->base->getAction()['uses']))->getStartLine();
        }

        $reflection = (new ReflectionClass($controller));

        if ($reflection->hasMethod($this->method())) {
            return $reflection->getMethod($this->method())->getStartLine();
        }

        return 0;
    }

    private function finalJsMethod(string $method): string
    {
        return match ($method) {
            'delete' => 'deleteMethod',
            default => $method,
        };
    }

    private function relativePath(string $path)
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }
}
