<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\Stringable;

class TypeScript
{
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
