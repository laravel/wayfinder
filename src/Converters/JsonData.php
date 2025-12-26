<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\JsonResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Wayfinder\Langs\TypeScript;

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
