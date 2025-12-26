<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\InertiaSharedData as SharedDataComponent;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;

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
