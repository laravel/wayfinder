<?php

namespace Laravel\Wayfinder\Support;

use function Illuminate\Filesystem\join_paths;

class Path
{
    protected static $basePaths = [];

    protected static $appPaths = [];

    public static function setBasePaths(...$paths): void
    {
        static::$basePaths = $paths;
    }

    public static function setAppPaths(...$paths): void
    {
        static::$appPaths = $paths;
    }

    public static function firstFromBasePath(string $path): ?string
    {
        foreach (static::$basePaths as $basePath) {
            if (file_exists(join_paths($basePath, $path))) {
                return join_paths($basePath, $path);
            }
        }

        return null;
    }

    public static function relativeToBasePath(string $path): string
    {
        foreach (static::$basePaths as $basePath) {
            foreach (array_unique([$basePath, realpath($basePath) ?: $basePath]) as $candidate) {
                $prefix = rtrim($candidate, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

                if (str_starts_with($path, $prefix)) {
                    return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($prefix)));
                }
            }
        }

        return $path;
    }
}
