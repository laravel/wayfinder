<?php

namespace Laravel\Wayfinder\Support;

use Laravel\Wayfinder\Langs\TypeScript;
use Throwable;

class EnumMeta
{
    /**
     * Write the values ranger resolved for each case as TypeScript literals.
     *
     * A value we cannot write as a literal is left out for that case alone, so
     * the rest of the case's meta still makes it through.
     *
     * @param  array<string, array<string, mixed>>  $meta
     * @return array<string, array<string, string>>
     */
    public static function literals(array $meta): array
    {
        $literals = [];

        foreach ($meta as $case => $values) {
            foreach ($values as $method => $value) {
                try {
                    $literals[$case][$method] = self::encode($value);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $literals;
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
            throw new UnsupportedEnumMetaValue;
        }

        return $encoded;
    }
}
