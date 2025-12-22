<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Ranger\Components\InertiaResponse;
use Laravel\Ranger\Components\Route;

class InertiaData extends Converter
{
    public function convert(InertiaResponse $response, Route $route): ?string
    {
        $fqn = str($response->component)
            ->replace(DIRECTORY_SEPARATOR, '.')
            ->prepend('Inertia.Pages.');
        $name = str($response->component)
            ->afterLast(DIRECTORY_SEPARATOR)
            ->afterLast('.');
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

        return $sharedData . ' & ' . TypeScript::objectToTypeObject($response->data, false);
    }
}
