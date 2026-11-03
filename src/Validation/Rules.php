<?php

namespace Laravel\Wayfinder\Validation;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\File as FileRule;
use Laravel\Ranger\Validation\Rule;
use Laravel\Wayfinder\Langs\TypeScript;
use ReflectionClass;

class Rules
{
    /**
     * Rules that only pass for string input.
     */
    protected const STRING_RULES = [
        'ActiveUrl', 'After', 'AfterOrEqual', 'Alpha', 'AlphaDash', 'AlphaNum', 'Ascii',
        'Before', 'BeforeOrEqual', 'CurrentPassword', 'Date', 'DateEquals', 'DateFormat',
        'DoesntEndWith', 'DoesntStartWith', 'Email', 'EndsWith', 'HexColor', 'Ip', 'Ipv4',
        'Ipv6', 'Json', 'Lowercase', 'MacAddress', 'NotRegex', 'Regex', 'StartsWith',
        'Timezone', 'Ulid', 'Uppercase', 'Url', 'Uuid',
    ];

    public function __construct(
        protected Collection $rules,
    ) {
        //
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

        if ($this->getRule('Nullable')) {
            return $baseType.' | null';
        }

        return $baseType;
    }

    protected function resolveBaseType(): string
    {
        $inValues = collect($this->getRule('In')?->getParams() ?? [])
            ->filter(fn ($v) => ! is_null($v) && $v !== '');

        if ($inValues->isNotEmpty()) {
            return $inValues->map(TypeScript::quote(...))->implode(' | ');
        }

        if ($enum = $this->rules->first(fn ($item) => $item->isEnum())) {
            $enumRule = new ReflectionClass($enum->rule());

            return TypeScript::classReference($enumRule->getProperty('type')->getValue($enum->rule()));
        }

        if (
            $this->getRule('File', 'Image', 'Mimes', 'Mimetypes', 'Extensions', 'Dimensions')
            || $this->rules->contains(fn (Rule $rule) => $rule->rule() instanceof FileRule)
        ) {
            return 'File';
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
                $object->key($param)->value('unknown')->optional();
            }

            return (string) $object;
        }

        if ($this->getRule(...self::STRING_RULES)) {
            return 'string';
        }

        return 'unknown';
    }

    protected function getRule(string ...$id): ?Rule
    {
        return $this->rules->first(fn (Rule $rule) => collect($id)->first(fn ($i) => $rule->is($i)));
    }
}
