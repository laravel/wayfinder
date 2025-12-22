<?php

namespace Laravel\Wayfinder\Registry;

use Laravel\Wayfinder\Langs\TypeScript;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use Laravel\Surveyor\Types;
use Laravel\Surveyor\Types\Contracts\Type;

class TypeScriptConverter extends AbstractConverter
{
    public function convert(Type $result): string
    {
        return match (get_class($result)) {
            Types\ArrayType::class => $this->convertArrayResult($result),
            Types\ArrayShapeType::class => $this->convertArrayShapeResult($result),
            Types\BoolType::class => $this->convertBoolResult($result),
            Types\ClassType::class => $this->convertClassResult($result),
            Types\IntType::class, Types\FloatType::class, Types\NumberType::class => $this->convertNumberResult($result),
            Types\IntersectionType::class => $this->convertIntersectionResult($result),
            Types\MixedType::class => $this->convertMixedResult($result),
            Types\NullType::class => $this->convertNullResult($result),
            Types\StringType::class => $this->convertStringResult($result),
            Types\UnionType::class => $this->convertUnionResult($result),
            Types\CallableType::class => $this->convertCallableResult($result),
            default => throw new InvalidArgumentException('Unsupported result type: ' . get_class($result)),
        };
    }

    protected function convertCallableResult(Types\CallableType $result): string
    {
        return $this->convert($result->returnType);
    }

    protected function convertArrayResult(Types\ArrayType $result): string
    {
        if ($result->value instanceof Collection) {
            $value = $result->value->toArray();
        } else {
            $value = $result->value;
        }

        $nullSuffix = $result->isNullable() ? ' | null' : '';

        if (array_is_list($value)) {
            $types = TypeScript::union(array_map($this->convert(...), $value));

            if (str_contains($types, '|')) {
                return '(' . $types . ')[]' . $nullSuffix;
            }

            return $types . '[]' . $nullSuffix;
        }

        return TypeScript::objectToRecord($value, false) . $nullSuffix;
    }

    protected function convertArrayShapeResult(Types\ArrayShapeType $result): string
    {
        $keyType = $this->convert($result->keyType);
        $valueType = $this->convert($result->valueType);

        if ($keyType === 'number') {
            return "{$valueType}[]";
        }

        if ($keyType === 'unknown') {
            $keyType = 'string';
        }

        return "Record<{$keyType}, {$valueType}>";
    }

    protected function convertBoolResult(Types\BoolType $result): string
    {
        $value = 'boolean';

        if ($result->value !== null) {
            $value = $result->value ? 'true' : 'false';
        }

        return $this->decorate($value, $result);
    }

    protected function convertClassResult(Types\ClassType $result): string
    {
        $value = str($result->value)->ltrim('\\');

        $matched = match ($value->toString()) {
            Stringable::class => 'string',
            Collection::class => 'unknown[]',
            default => $value->replace('\\', '.')->toString(),
        };

        return $this->decorate($matched, $result);
    }

    protected function convertNumberResult(Types\IntType|Types\FloatType|Types\NumberType $result): string
    {
        return $this->decorate('number', $result);
    }

    protected function decorate(string $type, Type $result): string
    {
        if ($result->isNullable()) {
            $type .= ' | null';
        }

        return $type;
    }

    protected function convertUnionResult(Types\UnionType $result): string
    {
        return $this->convertUnionOrIntersection($result->types, '|');
    }

    protected function convertIntersectionResult(Types\IntersectionType $result): string
    {
        return $this->convertUnionOrIntersection($result->types, '&');
    }

    protected function convertUnionOrIntersection(array $types, string $glue): string
    {
        $result = collect($types)
            ->map(function ($item) {
                if (is_array($item)) {
                    return collect($item)
                        ->filter()
                        ->map($this->convert(...))
                        ->unique()
                        ->implode(' | ');
                }

                if ($item === null) {
                    return null;
                }

                return $this->convert($item);
            })
            ->filter()
            ->unique();

        if ($result->count() > 1 && $result->contains(fn($type) => $type === 'unknown')) {
            $newResult = $result->filter(fn($type) => $type !== 'unknown');

            if ($newResult->count() === 1 && $newResult->first() === 'null') {
                return 'unknown';
            }

            $result = $newResult;
        }

        return $result->implode(' ' . $glue . ' ');
    }

    protected function convertMixedResult(Types\MixedType $result): string
    {
        return 'unknown';
    }

    protected function convertNullResult(Types\NullType $result): string
    {
        return 'null';
    }

    protected function convertStringResult(Types\StringType $result): string
    {
        return $this->decorate('string', $result);
    }

    protected function getType($type): string
    {
        if ($type instanceof Type) {
            return $this->convert($type);
        }

        return $type;
    }
}
