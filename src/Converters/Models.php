<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Ranger\Components\Model;

class Models extends Converter
{
    public function convert(Model $model): null
    {
        $properties = array_merge($model->getAttributes(), $model->getRelations());

        TypeScript::addFqnToNamespaced(
            $model->name,
            TypeScript::type(
                str($model->name)->afterLast('\\'),
                TypeScript::objectToTypeObject($properties, false),
            )->export(),
        );

        return null;
    }
}
