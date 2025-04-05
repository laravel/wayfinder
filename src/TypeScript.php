<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\Stringable;

class TypeScript
{
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

    public static function cleanUp(string $view): string
    {
        $replacements = [
            ' ,' => ',',
            '[ ' => '[',
            ' ]' => ']',
            ', }' => ' }',
            '} )' => '})',
            ' )' => ' )',
            '( ' => '(',
            '( ' => '(',
            "\n +" => ' +',
        ];

        return str($view)
            ->pipe(function (Stringable $str) {
                // Clean up function arguments
                $matches = $str->matchAll('/ = \(([^)]+\))/');

                foreach ($matches as $match) {
                    $str = $str->replaceFirst($match, preg_replace('/\s+/', ' ', $match));
                }

                return $str;
            })
            ->replaceMatches('/\n{3,}/', "\n\n")
            ->replace(array_keys($replacements), array_values($replacements))
            ->toString();
    }
}
