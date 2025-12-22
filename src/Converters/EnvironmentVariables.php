<?php

namespace Laravel\Wayfinder\Converters;

use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Results\Result;
use Illuminate\Support\Collection;
use Laravel\Ranger\Components\EnvironmentVariable;

class EnvironmentVariables extends Converter
{
    /**
     * @param  Collection<EnvironmentVariable>  $vars
     */
    public function convert(Collection $vars): ?Result
    {
        $viteVars = $vars->filter(
            fn(EnvironmentVariable $var) => str_starts_with($var->key, 'VITE_'),
        );

        if ($viteVars->isEmpty()) {
            return null;
        }

        $viteVars = $viteVars
            ->map(fn(EnvironmentVariable $var) => TypeScript::indent(
                "readonly {$var->key}: " . TypeScript::fromPhpType(gettype($var->value))
            ))
            ->join(PHP_EOL);

        $viteFile = <<<ENV
        /// <reference types="vite/client" />

        interface ImportMetaEnv {
        {$viteVars}
        }

        interface ImportMeta {
            readonly env: ImportMetaEnv
        }
        ENV;

        return new Result('vite-env.d.ts', $viteFile);
    }
}
