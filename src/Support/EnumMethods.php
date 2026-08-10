<?php

namespace Laravel\Wayfinder\Support;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Laravel\Wayfinder\Langs\TypeScript;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionNamedType;
use Stringable;
use Throwable;
use UnitEnum;

class EnumMethods
{
    protected const MAX_DEPTH = 10;

    /**
     * Resolve the value of every no-argument method for every case.
     *
     * The returned array is keyed by case name, then by method name, with
     * TypeScript literals as values. A method that throws or returns something
     * we can't represent is left out for that case alone, so methods that only
     * cover some of the cases still make it through.
     *
     * @param  class-string<UnitEnum>  $enum
     * @return array<string, array<string, string>>
     */
    public static function resolve(string $enum): array
    {
        $methods = self::eligibleMethods($enum);

        if ($methods === []) {
            return [];
        }

        $resolved = [];

        foreach ($enum::cases() as $case) {
            foreach ($methods as $method) {
                try {
                    $literal = self::literal($case->{$method}());
                } catch (Throwable) {
                    continue;
                }

                $resolved[$case->name][$method] = $literal;
            }
        }

        return $resolved;
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     * @return list<string>
     */
    protected static function eligibleMethods(string $enum): array
    {
        $reflection = new ReflectionEnum($enum);

        return collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(fn (ReflectionMethod $method) => $method->isStatic()
                || $method->getDeclaringClass()->getName() !== $reflection->getName()
                || str_starts_with($method->getName(), '__')
                || $method->getNumberOfRequiredParameters() > 0
                || $method->isGenerator()
                || self::returnsNothing($method))
            ->map(fn (ReflectionMethod $method) => $method->getName())
            ->values()
            ->all();
    }

    protected static function returnsNothing(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();

        return $type instanceof ReflectionNamedType
            && in_array(strtolower($type->getName()), ['void', 'never'], true);
    }

    protected static function literal(mixed $value): string
    {
        return self::encode(self::normalize($value));
    }

    protected static function encode(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '['.implode(', ', array_map(self::encode(...), $value)).']';
            }

            $object = TypeScript::object()->inline();

            foreach ($value as $key => $item) {
                $object->key((string) $key)->value(self::encode($item))->shorthand(false);
            }

            return (string) $object;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new UnsupportedEnumMethodValue;
        }

        return $encoded;
    }

    protected static function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            throw new UnsupportedEnumMethodValue;
        }

        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new UnsupportedEnumMethodValue;
            }

            return $value;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => self::normalize($item, $depth + 1), $value);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof JsonSerializable) {
            return self::normalize($value->jsonSerialize(), $depth + 1);
        }

        if ($value instanceof Arrayable) {
            return self::normalize($value->toArray(), $depth + 1);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        throw new UnsupportedEnumMethodValue;
    }
}
