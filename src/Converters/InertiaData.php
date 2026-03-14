<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Str;
use Laravel\Ranger\Components\InertiaResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Wayfinder\Langs\TypeScript;

class InertiaData extends Converter
{
    public function convert(InertiaResponse $response, Route $route): ?string
    {
        $fqn = str($response->component)
            ->explode('/')
            ->map(fn ($part) => Str::studly($part))
            ->prepend('Inertia.Pages')
            ->join('.');
        $name = str($response->component)
            ->afterLast('/')
            ->afterLast('.')
            ->studly();
        $type = $this->getType($response);

        // Store at the full path including the type name to avoid collisions
        // when a component has both a page and child pages (e.g., "Items" and "Items/Edit")
        $fullPath = $fqn.'.'.$name;

        TypeScript::addFqnToNamespaced($fullPath, TypeScript::type($name, $type)->export())
            ->referenceMethod($route->controller(), $route->method(), $route->controllerPath());

        return ($route->hasController()) ? $fullPath : null;
    }

    protected function getType(InertiaResponse $response): string
    {
        $sharedData = 'Inertia.SharedData';

        if (count($response->data) === 0) {
            return $sharedData;
        }

        return $sharedData.' & '.TypeScript::objectToTypeObject($response->data, false);
    }
}
