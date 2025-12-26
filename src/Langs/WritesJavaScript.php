<?php

namespace Laravel\Wayfinder\Langs;

trait WritesJavaScript
{
    public static function indent(string $string, int $level = 1): string
    {
        return collect(explode(PHP_EOL, $string))->map(fn ($line) => str_repeat(' ', $level * 4).$line)->implode(PHP_EOL);
    }

    public static function quote(string $string): string
    {
        foreach (['`', "'", '"'] as $quote) {
            if (str_starts_with($string, $quote)) {
                return $string;
            }
        }

        return '"'.$string.'"';
    }

    public static function quoteKey(string $key): string
    {
        if (str_starts_with($key, '[')) {
            return $key;
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
            return $key;
        }

        return self::quote($key);
    }

    protected static function splitOn(string $str, string $separator): array
    {
        $segments = [];
        $depth = 0;
        $current = '';

        foreach (str_split($str) as $character) {
            if (in_array($character, ['<', '(', '['])) {
                $depth++;
            } elseif (in_array($character, ['>', ')', ']'])) {
                $depth--;
            }

            if ($character === $separator && $depth === 0) {
                $segments[] = trim($current);
                $current = '';
            } else {
                $current .= $character;
            }
        }

        if (strlen($current) > 0) {
            $segments[] = trim($current);
        }

        return $segments;
    }
}
