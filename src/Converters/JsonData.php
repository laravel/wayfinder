<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Ranger\Components\JsonResponse;
use Laravel\Ranger\Components\Route;

class JsonData extends Converter
{
    public function convert(JsonResponse $response, Route $route): ?string
    {
        if ($route->hasController()) {
            return TypeScript::objectToRecord($response->data, false);
        }

        return null;
    }
}
