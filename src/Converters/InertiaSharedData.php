<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;
use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;

class InertiaSharedData extends Converter
{
    public function convert(SharedDataComponent $data): ?Result
    {
        TypeScript::addFqnToNamespaced(
            'Inertia.SharedData',
            TypeScript::type(
                'SharedData',
                TypeScript::fromSurveyorType($data->data),
            )->export(),
        );

        return null;
    }
}
