<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Str;
use Laravel\Ranger\Components\Model;
use Laravel\Wayfinder\Attributes\WayfinderPropertyType;
use Laravel\Wayfinder\Attributes\WayfinderType;
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

        // Apply #[WayfinderType] from cast classes, then #[WayfinderPropertyType] from the model
        if (class_exists($model->name)) {
            $reflection = new \ReflectionClass($model->name);

            $casts = [];

            // 1. Read $casts from the model
            if ($reflection->isInstantiable()) {
                $modelInstance = $reflection->newInstanceWithoutConstructor();
                $casts = method_exists($modelInstance, 'getCasts') ? $modelInstance->getCasts() : [];
            } else {
                $casts = $reflection->getDefaultProperties()['casts'] ?? [];
            }

            foreach ($casts as $property => $castClass) {
                if (! is_string($castClass) || ! class_exists($castClass)) {
                    continue;
                }

                $castAttrs = (new \ReflectionClass($castClass))->getAttributes(WayfinderType::class);

                if ($castAttrs) {
                    $properties[$property] = $castAttrs[0]->newInstance()->type;
                }
            }

            // 2. #[WayfinderPropertyType] on the model overrides everything (highest priority)
            foreach ($reflection->getAttributes(WayfinderPropertyType::class) as $attr) {
                $instance = $attr->newInstance();
                $properties[$instance->property] = $instance->type;
            }
        }

        TypeScript::addFqnToNamespaced(
            $model->name,
            TypeScript::type(
                str($model->name)->afterLast('\\'),
                TypeScript::objectToTypeObject($properties, false),
            )
                ->referenceClass($model->name, $model->filePath())
                ->export(),
        );

        return null;
    }
}
