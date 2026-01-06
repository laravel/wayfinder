<?php

namespace Laravel\Wayfinder\Support;

class Npm
{
    public static function isInstalled(string $packageName): bool
    {
        $packageJson = base_path('package.json');

        if (! file_exists($packageJson)) {
            return false;
        }

        $content = json_decode(file_get_contents($packageJson), true);

        return isset($content['devDependencies'][$packageName]) || isset($content['dependencies'][$packageName]);
    }

    public static function findFirstInstalledPackage(array $packageNames): ?string
    {
        return collect($packageNames)->first(fn ($package) => static::isInstalled($package));
    }
}
