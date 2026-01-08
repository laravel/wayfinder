<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Laravel\Ranger\Components\Model;
use Laravel\Wayfinder\Langs\TypeScript;
use ReflectionClass;
use ReflectionMethod;

class Models extends Converter
{
    public function convert(Model $model): null
    {
        $properties = array_merge(
            $model->getAttributes(),
            $model->getRelations(),
            $this->getComputedAttributes($model),
        );

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
            )
                ->referenceClass($model->name, $model->filePath())
                ->export(),
        );

        return null;
    }

    private function getComputedAttributes(Model $model): array
    {
        $reflection = new ReflectionClass($model->name);

        return collect($reflection->getMethods(ReflectionMethod::IS_PROTECTED))
            ->filter(fn (ReflectionMethod $method) => $method->getNumberOfParameters() === 0)
            ->filter(function (ReflectionMethod $method) {
                $type = $method->getReturnType();

                return $type
                    && ! $type->isBuiltin()
                    && $type->getName() === Attribute::class;
            })
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [
                    Str::snake($method->getName()) => $this->inferAttributeType($method),
                ];
            })
            ->all();
    }

    private function inferAttributeType(ReflectionMethod $method): string
    {
        $doc = $method->getDocComment();

        if ($doc && preg_match('/@return\s+Attribute<([^>]+)>/', $doc, $matches)) {
            $types = explode('|', $matches[1]);

            return collect($types)
                ->map(fn ($type) => TypeScript::fromPhpType(trim($type)))
                ->implode(' | ');
        }

        return 'unknown';
    }
}
