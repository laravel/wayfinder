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

        $dataType = $this->dataType($response->data);

        if ($response->isCollection) {
            $dataType = $this->group($dataType).'[]';
        }

        // Laravel nests an unwrapped resource under `data` whenever extra top-level keys
        // are added, so the payload never merges with `additional` at runtime.
        $wrap = $response->wrap ?? (empty($response->additional) ? null : 'data');

        if ($wrap === null) {
            return $dataType;
        }

        $outer = TypeScript::typeObject();
        $outer->key($wrap)->value($dataType);

        foreach ($response->additional as $key => $type) {
            $value = $type instanceof Type ? TypeScript::fromSurveyorType($type) : (string) $type;
            $outer->key($key)->value($value);
        }

        return (string) $outer;
    }

    /**
     * Older Ranger releases flatten every payload to an array, newer ones keep
     * non-array shapes (lists, unions) as Surveyor types.
     */
    protected function dataType(array|Type $data): string
    {
        return $data instanceof Type
            ? TypeScript::fromSurveyorType($data)
            : (string) TypeScript::objectToTypeObject($data, false);
    }

    protected function group(string $type): string
    {
        return str_contains($type, '|') ? "({$type})" : $type;
    }
}
