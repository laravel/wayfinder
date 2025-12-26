<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Str;
use Laravel\Ranger\Components\Model;
use Laravel\Wayfinder\Langs\TypeScript;

class Models extends Converter
{
    public function convert(Model $model): null
    {
        $properties = array_merge($model->getAttributes(), $model->getRelations());

        if ($model->snakeCaseAttributes()) {
            $properties = collect($properties)->mapWithKeys(
                fn ($value, $key) => [Str::snake($key) => $value],
            )->all();
        }

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
