<?php

namespace Laravel\Wayfinder\Langs;

use Illuminate\Support\Collection;
use Laravel\Surveyor\Types\Contracts\Type;
use Laravel\Wayfinder\Langs\TypeScript\ArrowFunctionBuilder;
use Laravel\Wayfinder\Langs\TypeScript\ObjectBuilder;
use Laravel\Wayfinder\Langs\TypeScript\TupleBuilder;
use Laravel\Wayfinder\Langs\TypeScript\TypeObjectBuilder;
use Laravel\Wayfinder\Langs\TypeScript\VariableBuilder;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;

class TypeScript
{
    use WritesJavaScript;

    protected static array $namespaced = [];

    protected static $safeImports = [];

    public const RESERVED_KEYWORDS = [
        'break',
        'case',
        'catch',
        'class',
        'const',
        'continue',
        'debugger',
        'default',
        'delete',
        'do',
        'else',
        'export',
        'extends',
        'false',
        'finally',
        'for',
        'function',
        'if',
        'import',
        'in',
        'instanceof',
        'new',
        'null',
        'return',
        'super',
        'switch',
        'this',
        'throw',
        'true',
        'try',
        'typeof',
        'var',
        'void',
        'while',
        'with',
    ];

    public static function literalUnion(string $type, array|Collection $values): VariableBuilder
    {
        if (is_array($values)) {
            $values = collect($values);
        }

        return self::type($type, $values->map(fn ($v) => self::quote($v))->implode(' | '));
    }

    public static function backtick(string $content): string
    {
        return '`'.$content.'`';
    }

    public static function block(string $content): VariableBuilder
    {
        return new VariableBuilder($content);
    }

    public static function templateString(string $content): string
    {
        return '${'.$content.'}';
    }

    public static function module(string $name, string|array $content)
    {
        if (is_array($content)) {
            $content = implode(PHP_EOL, $content);
        }

        return implode(PHP_EOL, [
            "declare module '{$name}' {",
            self::indent($content),
            '}',
        ]);
    }

    public static function union(string|array|Type $type)
    {
        if (is_array($type) && ! array_is_list($type)) {
            $type = self::objectToRecord($type, false);
        }

        if ($type instanceof Type) {
            $type = $type->value;
        }

        if (is_string($type)) {
            $type = explode('|', $type);
        }

        return collect($type)
            ->map(fn ($type) => trim($type))
            ->filter()
            ->unique()
            ->implode(' | ');
    }

    public static function tuple(...$items): TupleBuilder
    {
        return new TupleBuilder($items);
    }

    public static function constant(string $name, string $value): VariableBuilder
    {
        return self::block("const {$name} = {$value}");
    }

    public static function arrowFunction(?string $name = null): ArrowFunctionBuilder
    {
        return new ArrowFunctionBuilder($name);
    }

    public static function type(string $name, string $value): VariableBuilder
    {
        return self::block("type {$name} = {$value}");
    }

    public static function interface($name, $content): VariableBuilder
    {
        return self::block("interface {$name} {".PHP_EOL.$content.PHP_EOL.'}');
    }

    // public static function exportedObjectToRecord(string $name, array|Collection $values, $quote = true): string
    // {
    //     $record = self::objectToRecord($values, $quote);

    //     return "export type {$name} = {$record};";
    // }

    public static function objectKeyValue(string $key, string $value): VariableBuilder
    {
        $key = self::quoteKey($key);

        if ($key === $value) {
            return self::block($key);
        }

        return self::block($key.': '.$value);
    }

    public static function objectToRecord(array|Collection $values, $quote = true, $inline = false): ObjectBuilder|VariableBuilder
    {
        if (count($values) === 0) {
            return self::block('Record<string, never>');
        }

        $object = self::object()->inline($inline);

        foreach ($values as $key => $value) {
            $optional = $value instanceof Type && $value->isOptional();
            $value = $value instanceof Type ? self::fromSurveyorType($value) : $value;

            if (is_array($value) || $value instanceof Collection) {
                $value = self::objectToRecord($value, $quote);
            }

            $object
                ->key($key)
                ->value($value)
                ->optional($optional)
                ->quote($quote);
        }

        return $object;
    }

    public static function objectToTypeObject(array|Collection $values, $quote = true, $inline = false): TypeObjectBuilder|VariableBuilder
    {
        if (count($values) === 0) {
            return self::block('Record<string, never>');
        }

        $object = self::typeObject()->inline($inline);

        foreach ($values as $key => $value) {
            $optional = $value instanceof Type && $value->isOptional();
            $value = $value instanceof Type ? self::fromSurveyorType($value) : $value;

            if (is_array($value) || $value instanceof Collection) {
                $value = self::objectToTypeObject($value, $quote);
            }

            $object
                ->key($key)
                ->value($value)
                ->optional($optional)
                ->quote($quote);
        }

        return $object;
    }

    public static function object(): ObjectBuilder
    {
        return new ObjectBuilder;
    }

    public static function typeObject(): TypeObjectBuilder
    {
        return new TypeObjectBuilder;
    }

    public static function objectToJsRecord(array|Collection $values, $quote = true, $flat = false): VariableBuilder
    {
        $obj = [];

        foreach ($values as $key => $value) {
            $optional = $value instanceof Type && $value->isOptional();

            $value = $value instanceof Type ? self::fromSurveyorType($value) : $value;

            if (is_array($value) || $value instanceof Collection) {
                $value = self::objectToRecord($value, $quote);
            }

            $colon = $optional ? '?:' : ':';

            $formatted = $quote ? self::quote($value) : $value;
            $key = self::quoteKey($key);
            $obj[] = $flat ? "{$key}{$colon} {$formatted}" : self::indent("{$key}{$colon} {$formatted}");
        }

        $type = ['{', implode($flat ? ' ' : ','.PHP_EOL, $obj), '}'];

        return self::block(implode($flat ? ' ' : PHP_EOL, $type));
    }

    public static function objectWithOnlyKeys(array|Collection $keys): string
    {
        $keys = is_array($keys) ? collect($keys) : $keys;

        return '{ '.$keys->implode(', ').' }';
    }

    public static function fqn(...$parts): string
    {
        return implode('\\', $parts);
    }

    public static function addFqnToNamespaced(string|array $path, string $content): VariableBuilder
    {
        if (is_array($path)) {
            $path = implode('.', $path);
        }

        $path = str($path)->ltrim('/\\')->replace(['\\', '/'], '.')->toString();

        $block = self::block($content);

        self::$namespaced[$path] ??= [];
        self::$namespaced[$path][] = $block;

        return $block;
    }

    public static function dockblock(array $meta): string
    {
        $lines = collect($meta)->map(fn ($line) => " * {$line}");

        return '/**'.PHP_EOL.$lines->implode(PHP_EOL).PHP_EOL.' */';
    }

    public static function getNamespaced(): Collection
    {
        return collect(self::$namespaced);
    }

    public static function getNamespacedFormatted(): Collection
    {
        $lines = self::formatNamespaced(self::getNamespaced()->undot())->flatten();

        return $lines->map(function ($line, $index) use ($lines) {
            $previousLine = $lines->get($index - 1, '');

            if (str_contains($previousLine, 'export namespace') || $index === 0) {
                return $line;
            }

            if (str_contains($line, 'export namespace') || str_contains($line, 'export type')) {
                return PHP_EOL.$line;
            }

            return $line;
        });
    }

    protected static function formatNamespaced(Collection $namespaced, $indent = 0): Collection
    {
        return $namespaced->map(function ($content, $key) use ($indent) {
            if (array_is_list($content)) {
                return collect($content)->map(
                    fn ($c) => collect(explode(PHP_EOL, $c))->map(fn ($line) => self::indent($line, $indent))->implode(PHP_EOL)
                )->implode(PHP_EOL);
            }

            $safeKey = self::safeMethod($key, '_');

            return [
                self::indent("export namespace {$safeKey} {", $indent),
                self::formatNamespaced(collect($content), $indent + 1),
                self::indent('}', $indent),
            ];
        });
    }

    public static function safeImport(string $import): string
    {
        self::$safeImports[$import] ??= $import.ucfirst(substr(hash('xxh128', $import), 0, 7));

        return self::$safeImports[$import];
    }

    public static function fromSurveyorType(Type $type): string
    {
        return ResultConverter::to($type, TypeScriptConverter::class);
    }

    public static function fromPhpType(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer', 'float', 'double' => 'number',
            'string' => 'string',
            'bool', 'boolean' => 'boolean',
            'array' => 'unknown[]',
            'object' => 'Record<string, unknown>',
            'resource', 'unknown type', 'resource (closed)' => 'unknown',
            'null' => 'null',
            'unknown type' => 'unknown',
            default => throw new \InvalidArgumentException("Unsupported PHP type: {$type}"),
        };
    }

    public static function safeMethod(string $method, string $suffix): string
    {
        $method = str($method);

        if ($method->contains('-')) {
            $method = $method->camel();
        }

        $suffix = strtolower($suffix);

        if (in_array($method, self::RESERVED_KEYWORDS)) {
            return $method->append(ucfirst($suffix));
        }

        if (is_numeric(substr($method, 0, 1))) {
            return $method->prepend($suffix);
        }

        return $method;
    }
}
