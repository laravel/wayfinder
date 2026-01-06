<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;
use Laravel\Surveyor\Types\ArrayShapeType;
use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;

class InertiaSharedData extends Converter
{
    /**
     * @return array<Result>
     */
    public function convert(SharedDataComponent $data): array
    {
        TypeScript::addFqnToNamespaced(
            'Inertia.SharedData',
            TypeScript::type(
                'SharedData',
                TypeScript::fromSurveyorType($data->data),
            )->export(),
        );

        $results = [];

        if ($moduleOverrideResult = $this->moduleOverrideResult($data)) {
            $results[] = $moduleOverrideResult;
        }

        return $results;
    }

    protected function moduleOverrideResult(SharedDataComponent $data): ?Result
    {
        $object = ['sharedPageProps' => $data->data->value];

        if ($data->withAllErrors) {
            $object['errorValueType'] = TypeScript::fromSurveyorType(new ArrayShapeType(Type::int(), Type::string()));
        }

        $module = TypeScript::module(
            '@inertiajs/core',
            TypeScript::interface(
                'InertiaConfig',
                trim(TypeScript::objectToTypeObject($object, false), '{}'),
            )->export(),
        );

        return new Result('inertia-config.d.ts', $module);
    }
}
