<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Ranger\Components\Enum;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;
use Laravel\Wayfinder\Support\EnumMethods;

class Enums extends Converter
{
    protected bool $withMethods = false;

    public function withMethods(bool $withMethods = true): static
    {
        $this->withMethods = $withMethods;

        return $this;
    }

    public function convert(Enum $enum): Result
    {
        $name = str($enum->name)->afterLast('\\')->toString();
        $path = str_replace('\\', '/', $enum->name);

        TypeScript::addFqnToNamespaced(
            $path,
            TypeScript::type(
                $name,
                $enum->cases === []
                    ? 'never'
                    : TypeScript::union(
                        collect($enum->cases)
                            ->map(fn ($case) => is_string($case) ? "'{$case}'" : (string) $case)
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

        if ($content !== []) {
            $content[] = '';
        }

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

        $methods = $this->withMethods && $enum->cases !== []
            ? EnumMethods::resolve($enum->name)
            : [];

        if ($methods !== []) {
            $content[] = '';
            $content[] = $this->methodConstant($name, $enum, $methods);
        }

        $content[] = '';
        $content[] = TypeScript::block($name)->exportDefault();

        return new Result($path.'.ts', implode(PHP_EOL, $content));
    }

    /**
     * @param  array<string, array<string, string>>  $methods
     */
    protected function methodConstant(string $name, Enum $enum, array $methods): string
    {
        $obj = TypeScript::object();

        // Keyed by case value rather than case name so a value handed back by
        // the server can be used as the lookup key directly.
        foreach ($enum->cases as $case => $value) {
            if (! isset($methods[$case])) {
                continue;
            }

            $values = TypeScript::object();

            foreach ($methods[$case] as $method => $literal) {
                $values->key($method)->value($literal);
            }

            $obj->key((string) $value)->value((string) $values);
        }

        return (string) TypeScript::constant($this->methodConstantName($name, $enum), (string) $obj)
            ->export()
            ->asConst()
            ->link($enum->name, $enum->filepath());
    }

    protected function methodConstantName(string $name, Enum $enum): string
    {
        $taken = array_keys($enum->cases);
        $constant = $name.'Meta';

        while (in_array($constant, $taken, true)) {
            $constant .= 'Meta';
        }

        return $constant;
    }
}
