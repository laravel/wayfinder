<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\JsonApiResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Surveyor\Types\Contracts\Type;
use Laravel\Wayfinder\Langs\TypeScript;

class JsonApiData extends Converter
{
    public function convert(JsonApiResponse $response, Route $route): ?string
    {
        if (! $route->hasController()) {
            return null;
        }

        $resourceObject = TypeScript::typeObject();
        $resourceObject->key('id')->value('string');
        $resourceObject->key('type')->value('string');

        if (! empty($response->attributes)) {
            $resourceObject->key('attributes')
                ->value((string) TypeScript::objectToTypeObject($response->attributes, false))
                ->optional();
        }

        if (! empty($response->relationships)) {
            $relObj = TypeScript::typeObject();

            foreach ($response->relationships as $name => $relType) {
                $relObj->key($name)
                    ->value('{ data: { id: string, type: string } | null }')
                    ->optional();
            }

            $resourceObject->key('relationships')
                ->value((string) $relObj)
                ->optional();
        }

        if (! empty($response->links)) {
            $resourceObject->key('links')
                ->value((string) TypeScript::objectToTypeObject($response->links, false))
                ->optional();
        }

        if (! empty($response->meta)) {
            $resourceObject->key('meta')
                ->value((string) TypeScript::objectToTypeObject($response->meta, false))
                ->optional();
        }

        $dataType = (string) $resourceObject;

        if ($response->isCollection) {
            return '{ data: Array<'.$dataType.'> }';
        }

        return '{ data: '.$dataType.' }';
    }
}
