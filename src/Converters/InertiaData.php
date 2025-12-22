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
            ->explode(DIRECTORY_SEPARATOR)
            ->map(fn ($part) => Str::studly($part))
            ->prepend('Inertia.Pages')
            ->join('.');
        $name = str($response->component)
            ->afterLast(DIRECTORY_SEPARATOR)
            ->afterLast('.')
            ->explode(DIRECTORY_SEPARATOR)
            ->map(fn ($part) => Str::studly($part))
            ->join(DIRECTORY_SEPARATOR);
        $type = $this->getType($response);

        TypeScript::addFqnToNamespaced($fqn, TypeScript::type($name, $type)->export())
            ->referenceMethod($route->controller(), $route->method(), $route->controllerPath());

        return ($route->hasController()) ? $fqn : null;
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
