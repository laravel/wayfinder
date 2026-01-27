<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\Enum;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;

class Enums extends Converter
{
    public function convert(Enum $enum): Result
    {
        $name = str($enum->name)->afterLast('\\')->toString();
        $path = str_replace('\\', '/', $enum->name);

        TypeScript::addFqnToNamespaced(
            $path,
            TypeScript::type(
                $name,
                TypeScript::union(
                    collect($enum->cases)
                        ->map(fn ($case) => "'{$case}'")
                        ->values()
                        ->all(),
                ),
            )
                ->referenceClass($enum->name, $enum->filePath())
                ->export(),
        );

        $content = [];

        foreach ($enum->cases as $case => $value) {
            if (is_string($value)) {
                $content[] = TypeScript::constant($case, TypeScript::quote($value))->export();
            } else {
                $content[] = TypeScript::constant($case, $value)->export();
            }
        }

        $content[] = '';

        $obj = TypeScript::objectWithOnlyKeys(array_keys($enum->cases));

        $content[] = TypeScript::constant($name, $obj)
            ->export()
            ->asConst()
            ->link($enum->name, $enum->filepath());

        $content[] = '';
        $content[] = TypeScript::block($name)->exportDefault();

        return new Result($path.'.ts', implode(PHP_EOL, $content));
    }
}
