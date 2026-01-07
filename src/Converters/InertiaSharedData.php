<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;
use Laravel\Surveyor\Types\ArrayShapeType;
use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Langs\TypeScript\Imports;
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

        $typeObject = TypeScript::objectToTypeObject($object, false);

        preg_match_all('/(?<!\.)([A-Z][a-zA-Z0-9]*)(?=\.[A-Z])/', $typeObject, $matches);

        $imports = [];

        if (count($matches[0]) > 0) {
            $imports[] = (string) Imports::create()->add('./types', $matches[0]);
            $imports[] = '';
            $imports[] = '';
        }

        $module = TypeScript::module(
            '@inertiajs/core',
            TypeScript::interface(
                'InertiaConfig',
                trim($typeObject, '{}'),
            )->export(),
        );

        return new Result('inertia-config.d.ts', implode(PHP_EOL, $imports).$module);
    }
}
