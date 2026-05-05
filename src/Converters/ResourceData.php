<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\ResourceResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Surveyor\Types\Contracts\Type;
use Laravel\Wayfinder\Langs\TypeScript;

class ResourceData extends Converter
{
    public function convert(ResourceResponse $response, Route $route): ?string
    {
        if (! $route->hasController()) {
            return null;
        }

        $dataType = (string) TypeScript::objectToTypeObject($response->data, false);

        if ($response->isCollection) {
            $dataType = "{$dataType}[]";
        }

        if ($response->wrap !== null) {
            $outer = TypeScript::typeObject();
            $outer->key($response->wrap)->value($dataType);

            foreach ($response->additional as $key => $type) {
                $value = $type instanceof Type ? TypeScript::fromSurveyorType($type) : (string) $type;
                $outer->key($key)->value($value);
            }

            return (string) $outer;
        }

        // No wrapping — merge additional into data shape if present
        if (! empty($response->additional)) {
            $combined = array_merge($response->data, $response->additional);

            return (string) TypeScript::objectToTypeObject($combined, false);
        }

        return $dataType;
    }
}
