<?php

namespace TiMacDonald\Wayfinder;

class TypeScript
{
    public static function clean(string $view): string
    {
        return str($view)
            ->replaceMatches('/\s+/', ' ')
            ->replace(' ,', ',')
            ->replace('[ ', '[')
            ->replace(' ]', ']')
            ->replace(', }', ' }')
            ->toString();
    }
}
