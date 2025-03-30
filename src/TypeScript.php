<?php

namespace Laravel\Wayfinder;

class TypeScript
{
    public static function trimDeadspace(string $view): string
    {
        return str($view)
            ->replaceMatches('/\s+/', ' ')
            ->replace(' ,', ',')
            ->replace('[ ', '[')
            ->replace(' ]', ']')
            ->replace(', }', ' }')
            ->toString();
    }

    public static function cleanUp(string $view): string
    {
        $replacements = [
            ' ,' => ',',
            '[ ' => '[',
            ' ]' => ']',
            ', }' => ' }',
            ' )' => ' )',
            '( ' => '(',
            '( ' => '(',
            "\n +" => ' +',
        ];

        return str($view)
            ->replaceMatches('/\n{3,}/', "\n\n")
            ->replace(array_keys($replacements), array_values($replacements))
            ->toString();
    }
}
