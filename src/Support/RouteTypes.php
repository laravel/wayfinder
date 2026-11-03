<?php

namespace Laravel\Wayfinder\Support;

use Illuminate\Support\Str;
use Laravel\Ranger\Components\Route;
use Laravel\Wayfinder\Langs\Concerns\HasMeta;
use Laravel\Wayfinder\Langs\TypeScript;

class RouteTypes
{
    /**
     * Every closure route reports itself as \Closure::__invoke, so its name is
     * the only stable thing that tells one apart from another. An unnamed
     * closure has nothing to go by and gets no namespace.
     */
    public static function namespace(Route $route): ?string
    {
        if ($route->hasController()) {
            return TypeScript::fqn($route->controller(), ucwords($route->method()));
        }

        $name = $route->name();

        if ($name === null) {
            return null;
        }

        return collect(explode('.', $name))
            ->map(fn (string $segment) => TypeScript::safeMethod(Str::studly($segment), '_'))
            ->prepend('Closure')
            ->implode('\\');
    }

    /**
     * @param  HasMeta  $block
     */
    public static function reference($block, Route $route): void
    {
        if ($route->hasController()) {
            $block->referenceMethod($route->controller(), $route->method(), $route->controllerPath());

            return;
        }

        $block->referenceFile($route->controllerPath(), $route->methodLineNumber());
    }
}
