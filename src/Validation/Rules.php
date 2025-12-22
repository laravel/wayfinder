<?php

namespace Laravel\Wayfinder\Validation;

use Laravel\Wayfinder\Langs\TypeScript;
use Illuminate\Support\Collection;
use Laravel\Ranger\Validation\Rule;
use ReflectionClass;

class Rules
{
    public function __construct(
        protected Collection $rules,
    ) {
        //
    }

    public function isRequired(): bool
    {
        return $this->getRule('Required') !== null;
    }

    public function resolveFieldType(): string
    {
        $baseType = $this->resolveBaseType();

        if ($this->getRule('Nullable')) {
            return $baseType . ' | null';
        }

        return $baseType;
    }

    protected function resolveBaseType(): string
    {
        if ($inRule = $this->getRule('In')) {
            return collect($inRule->getParams())->map(TypeScript::quote(...))->implode(' | ');
        }

        if ($this->getRule('String')) {
            return 'string';
        }

        if ($this->getRule('Integer', 'Numeric', 'Digits', 'DigitsBetween')) {
            return 'number';
        }

        if ($this->getRule('Boolean') || $this->getRule('Accepted')) {
            return 'boolean';
        }

        if ($this->getRule('Decimal')) {
            // https://laravel.com/docs/validation#rule-decimal
            return '`${number}.${number}`';
        }

        if ($arrayRule = $this->getRule('Array')) {
            if (! $arrayRule->hasParams()) {
                return 'unknown[]';
            }

            // https://laravel.com/docs/validation#rule-array
            $object = TypeScript::typeObject();

            foreach ($arrayRule->getParams() as $param) {
                $object->key($param)->value('unknown')->quote();
            }

            return $object . '[]';
        }

        if ($enum = $this->rules->first(fn($item) => $item->isEnum())) {
            $enumRule = new ReflectionClass($enum->rule());

            return str_replace('\\', '.', $enumRule->getProperty('type')->getValue($enum->rule()));
        }

        return 'string';
    }

    protected function getRule(string ...$id): ?Rule
    {
        return $this->rules->first(fn(Rule $rule) => collect($id)->first(fn($i) => $rule->is($i)));
    }
}
