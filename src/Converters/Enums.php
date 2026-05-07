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
            if (in_array($case, TypeScript::RESERVED_KEYWORDS, true)) {
                continue;
            }

            if (is_string($value)) {
                $content[] = TypeScript::constant($case, TypeScript::quote($value))->export();
            } else {
                $content[] = TypeScript::constant($case, $value)->export();
            }
        }

        $content[] = '';

        $obj = TypeScript::object()->inline();

        foreach ($enum->cases as $case => $value) {
            if (in_array($case, TypeScript::RESERVED_KEYWORDS, true)) {
                $literal = is_string($value) ? TypeScript::quote($value) : (string) $value;
                $obj->key($case)->value($literal);
            } else {
                $obj->key($case)->value($case);
            }
        }

        $content[] = TypeScript::constant($name, (string) $obj)
            ->export()
            ->asConst()
            ->link($enum->name, $enum->filepath());

        $content[] = '';
        $content[] = TypeScript::block($name)->exportDefault();

        return new Result($path.'.ts', implode(PHP_EOL, $content));
    }
}
