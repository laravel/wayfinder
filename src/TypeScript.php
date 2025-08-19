<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\Stringable;

class TypeScript
{
    public const RESERVED_KEYWORDS = [
        'await',
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
        'enum',
        'export',
        'extends',
        'false',
        'finally',
        'for',
        'function',
        'if',
        'implements',
        'import',
        'in',
        'instanceof',
        'interface',
        'let',
        'new',
        'null',
        'package',
        'private',
        'protected',
        'public',
        'return',
        'static',
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
        'yield',
    ];

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

        if ($method->match('/^[a-zA-Z_$]/')->isEmpty()) {
            return $method->prepend($suffix);
        }

        return $method;
    }

    public static function quoteIfNeeded(string $name): string
    {
        if (is_numeric($name)) {
            return $name;
        }

        if (is_numeric($name[0])) {
            return '"'.$name.'"';
        }

        return $name;
    }

    public static function cleanUp(string $view): string
    {
        $replacements = [
            ' ,' => ',',
            '[ ' => '[',
            ', }' => ' }',
            '} )' => '})',
            ' )' => ')',
            '( ' => '(',
            '( ' => '(',
            PHP_EOL.' +' => ' +',
            '})'.PHP_EOL.'/**' => '})'.PHP_EOL.PHP_EOL.'/**',
            '}'.PHP_EOL.'/**' => '}'.PHP_EOL.PHP_EOL.'/**',
        ];

        $regexReplacements = [
            '/\=\> \{\n{2,}/' => '=> {'.PHP_EOL,
            '/\\s+\.replace/' => sprintf('%s%s.replace', PHP_EOL, str_repeat(' ', 12)),
            '/\s+\+ queryParams\(options\)/' => ' + queryParams(options)',
            '/\n{3,}/' => "\n\n",
        ];

        return str($view)
            ->pipe(function (Stringable $str) {
                // Clean up function arguments
                $matches = $str->matchAll('/ = \(([^)]+\))/')
                    ->concat($str->matchAll('/\.url\(\s*args,\s+\{/'))
                    ->concat($str->matchAll('/\.url\(\s*args,\s+options\s*\)/'))
                    ->concat($str->matchAll('/\.url\(\s*options\s*\)/'))
                    ->concat($str->matchAll('/\(\s+\{/'))
                    ->concat($str->matchAll('/\}\s+\)/'));

                foreach ($matches as $match) {
                    $str = $str->replaceFirst($match, preg_replace('/\s+/', ' ', $match));
                }

                return $str;
            })
            ->pipe(function (Stringable $str) {
                $depth = 0;

                return str(
                    $str->explode(PHP_EOL)
                        ->map(fn ($s) => trim($s))
                        ->map(function ($s) use (&$depth) {
                            if ($s === '') {
                                return $s;
                            }

                            if (str_starts_with($s, '}') || str_starts_with($s, ']')) {
                                $depth--;
                            }

                            $line = str_repeat(' ', $depth * 4).$s;

                            if (str_ends_with($s, '{') || str_ends_with($s, '[')) {
                                $depth++;
                            }

                            return $line;
                        })
                        ->implode(PHP_EOL)
                );
            })
            ->replaceMatches(array_keys($regexReplacements), array_values($regexReplacements))
            ->replace(array_keys($replacements), array_values($replacements))
            ->toString();
    }
}
