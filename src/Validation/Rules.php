<?php

namespace Laravel\Wayfinder\Validation;

use Illuminate\Support\Collection;
use Laravel\Ranger\Validation\Rule as RangerRule;
use Laravel\Wayfinder\Langs\TypeScript;
use ReflectionClass;

class Rules
{
    public function __construct(
        protected Collection $rules,
    ) {
        $this->rules = $this->rules->map(function ($rule) {
            if ($rule instanceof Rule || $rule instanceof RangerRule) {
                return $rule;
            }

            return new Rule($rule);
        });
    }

    public function isRequired(): bool
    {
        return $this->getRule('Required') !== null;
    }

    public function isNullable(): bool
    {
        return $this->getRule('Nullable') !== null;
    }

    public function resolveFieldType(): string
    {
        $baseType = $this->resolveBaseType();

        if ($this->isNullable()) {
            return $baseType.' | null';
        }

        return $baseType;
    }

    protected function resolveBaseType(): string
    {
        if ($inRule = $this->getRule('In')) {
            return collect($inRule->getParams())
                ->flatten()
                ->filter(fn ($v) => ! is_null($v) && $v !== '')
                ->map(fn ($v) => TypeScript::quote((string) $v))
                ->implode(' | ');
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

            return $object.'[]';
        }

        if ($enum = $this->rules->first(fn ($item) => $item->isEnum())) {
            $enumRule = new ReflectionClass($enum->rule());

            return str_replace('\\', '.', $enumRule->getProperty('type')->getValue($enum->rule()));
        }

        return 'string';
    }

    protected function getRule(string ...$id): Rule|RangerRule|null
    {
        return $this->rules->first(fn ($rule) => collect($id)->first(fn ($i) => $rule->is($i)));
    }
}
