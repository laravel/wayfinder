<?php

namespace Laravel\Wayfinder;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;

class CurrentRouteService
{
    private ?string $forcedScheme;

    private ?string $forcedRoot;

    public function __construct(
        private Filesystem $files,
        private UrlGenerator $url,
    ) {
        //
    }

    public function generate(
        string $originalContent,
        Collection $routes,
        ?string $forcedScheme,
        ?string $forcedRoot,
        string $basePath,
    ): string {
        $this->forcedScheme = $forcedScheme;
        $this->forcedRoot = $forcedRoot;
        $indexImportPath = './index';
        $routesImportPath = './routes';

        $namedRoutes = $this->getNamedRoutesWithUrls($routes);

        // Generate the routes.ts file content
        $this->generateRoutesFile($namedRoutes, $basePath, $indexImportPath);

        // Return the updated wayfinder content
        $routeTypesJs = $this->generateNamedRoutesTypes($namedRoutes);
        $content = $this->generateCurrentRouteContent($originalContent, $routesImportPath, $routeTypesJs);

        return $content;
    }

    private function generateRoutesFile(array $namedRoutes, string $basePath, string $indexImportPath): void
    {
        $routesPath = $basePath.'/routes.ts';
        $this->files->ensureDirectoryExists(dirname($routesPath));

        $namedRoutesJs = $this->generateNamedRoutesJs($namedRoutes);
        $wildcardValidation = $this->generateWildcardValidationFunction($namedRoutes);
        $checkQueryParams = $this->generateCheckQueryParams();

        $imports = "import type { RouteArguments, RoutePrimitive, RouteObject } from '{$indexImportPath}'";

        $content = <<<TYPESCRIPT
        {$imports};

        {$namedRoutesJs}

        {$wildcardValidation}

        {$checkQueryParams}
        TYPESCRIPT;

        $this->files->put($routesPath, $content);
    }

    private function getNamedRoutesWithUrls(Collection $routes): array
    {
        $namedRoutes = [];

        foreach ($routes as $route) {
            $name = $route->name();

            if ($name) {
                try {
                    $url = rtrim($this->url->route($name), '/');
                    $namedRoutes[$name] = $url;
                } catch (Exception) {
                    $uri = $route->uri();

                    $uri = trim($uri, "'\"");

                    $domain = $route->domain();
                    $scheme = $this->forcedScheme ?? 'https';
                    $root = $this->forcedRoot ?? '';

                    $appUrl = config('app.url');
                    $appUrl = rtrim($appUrl, '/');
                    $base = $domain ? $domain : parse_url($appUrl, PHP_URL_HOST);

                    $fullUrl = $scheme.'://'.$base.$root.'/'.ltrim($uri, '/');
                    $namedRoutes[$name] = rtrim($fullUrl, '/');
                }
            }
        }

        return $namedRoutes;
    }

    private function generateNamedRoutesTypes(array $namedRoutes): string
    {
        if (empty($namedRoutes)) {
            return 'export type NamedRoute = never;'."\n\n".
                'export type WildcardRoute = never;'."\n\n".
                'export type RouteName = never;';
        }

        $routeNames = array_keys($namedRoutes);

        // Generate exact route names
        $exactRoutes = "'".implode("' | '", $routeNames)."'";

        // Generate optimized wildcard patterns
        $wildcardPatterns = $this->generateOptimizedWildcards($routeNames);
        $wildcardUnion = empty($wildcardPatterns) ? 'never' : "'".implode("' | '", $wildcardPatterns)."'";

        return "export type NamedRoute = {$exactRoutes};"."\n\n".
            "export type WildcardRoute = {$wildcardUnion};"."\n\n".
            'export type RouteName = NamedRoute | WildcardRoute;';
    }

    private function generateOptimizedWildcards(array $routeNames): array
    {
        $wildcards = [];

        // Group routes by their segments for analysis
        $routeGroups = [];
        foreach ($routeNames as $route) {
            $parts = explode('.', $route);
            $routeGroups[] = $parts;
        }

        // Generate prefix wildcards (posts.* for posts.index, posts.show, etc.)
        $prefixCounts = [];
        foreach ($routeGroups as $parts) {
            // Only check meaningful prefixes (skip single segments)
            for ($i = 1; $i < count($parts); $i++) {
                $prefix = implode('.', array_slice($parts, 0, $i));
                $prefixCounts[$prefix] = ($prefixCounts[$prefix] ?? 0) + 1;
            }
        }

        // Add prefix wildcards with 2+ occurrences
        foreach ($prefixCounts as $prefix => $count) {
            if ($count >= 2) {
                $wildcards[] = $prefix.'.*';
            }
        }

        // Generate suffix wildcards (*.create for posts.create, users.create, etc.)
        $suffixCounts = [];
        foreach ($routeGroups as $parts) {
            // Check all possible suffixes
            for ($i = 1; $i < count($parts); $i++) {
                $suffix = implode('.', array_slice($parts, $i));
                $suffixCounts[$suffix] = ($suffixCounts[$suffix] ?? 0) + 1;
            }
        }

        // Add suffix wildcards with 2+ occurrences
        foreach ($suffixCounts as $suffix => $count) {
            if ($count >= 2) {
                $wildcards[] = '*.'.$suffix;
            }
        }

        // Remove duplicates and sort for consistency
        $wildcards = array_unique($wildcards);
        sort($wildcards);

        return $wildcards;
    }

    private function generateWildcardValidationFunction(array $namedRoutes): string
    {
        $routeNames = array_keys($namedRoutes);
        $wildcards = $this->generateOptimizedWildcards($routeNames);

        if (empty($wildcards)) {
            return <<<'JAVASCRIPT'
            /**
             * Validates if a wildcard pattern matches existing routes
             * This function is auto-generated based on your application's routes
             */
            export const isValidWildcardPattern = (pattern: string): boolean => {
                return false;
            };
            JAVASCRIPT;
        }

        // Generate arrays for prefix and suffix wildcards
        $prefixWildcards = [];
        $suffixWildcards = [];

        foreach ($wildcards as $wildcard) {
            if (str_starts_with($wildcard, '*.')) {
                $suffixWildcards[] = "'".substr($wildcard, 2)."'";
            } elseif (str_ends_with($wildcard, '.*')) {
                $prefixWildcards[] = "'".substr($wildcard, 0, -2)."'";
            }
        }

        $prefixArray = empty($prefixWildcards) ? '[]' : '['.implode(', ', $prefixWildcards).']';
        $suffixArray = empty($suffixWildcards) ? '[]' : '['.implode(', ', $suffixWildcards).']';

        return <<<JAVASCRIPT
        /**
         * Validates if a wildcard pattern matches existing routes
         * This function is auto-generated based on your application's routes
         */
        export const isValidWildcardPattern = (pattern: string): boolean => {
            const prefixWildcards: string[] = {$prefixArray};
            const suffixWildcards: string[] = {$suffixArray};
            const routeNames = Object.keys(namedRoutes);
            
            if (pattern.startsWith('*.')) {
                const suffix = pattern.substring(2);
                return suffixWildcards.includes(suffix) && 
                    routeNames.some(route => route.endsWith('.' + suffix));
            }
            
            if (pattern.endsWith('.*')) {
                const prefix = pattern.substring(0, pattern.length - 2);
                return prefixWildcards.includes(prefix) && 
                    routeNames.some(route => route.startsWith(prefix + '.'));
            }
            
            return false;
        };
        JAVASCRIPT;
    }

    private function generateCheckQueryParams(): string
    {
        return <<<'JAVASCRIPT'
        // Validate query params of the current URL against provided RouteArguments (excluding route path params)
        export const checkQueryParams = (
            currentUrlObj: URL,
            name: string,
            routeParams: RouteArguments,
            namedRoutes: Record<string, string>,
        ): boolean => {
            if (routeParams === null || typeof routeParams !== 'object') return true;

            const toQueryValue = (v: RoutePrimitive) => v === true ? '1' : v === false ? '0' : String(v);
            const buildKey = (p: string, k: string) => (p && p.length > 0) ? `${p}[${k}]` : k;

            const isRouteParamKey = (key: string): boolean => {
                const tpl = namedRoutes[name] ?? '';
                return tpl.includes(`{${key}}`) || tpl.includes(`{${key}?}`);
            };

            const expected = new Map<string, string[]>();
            const add = (key: string, val: RoutePrimitive | RoutePrimitive[] | null | undefined | RouteObject) => {
                if (val == null) return;
                if (Array.isArray(val)) {
                    const k = `${key}[]`;
                    const arr = expected.get(k) ?? [];
                    for (const item of val) arr.push(toQueryValue(item));
                    expected.set(k, arr);
                    return;
                }
                if (typeof val === 'object') {
                    for (const [ck, cv] of Object.entries(val)) add(buildKey(key, ck), cv as RoutePrimitive);
                    return;
                }
                const arr = expected.get(key) ?? [];
                arr.push(toQueryValue(val));
                expected.set(key, arr);
            };

            for (const [key, value] of Object.entries(routeParams)) {
                // Skip route parameters unless they are arrays (arrays should always be treated as query params)
                if (isRouteParamKey(key) && !Array.isArray(value)) continue;
                add(key, value as RoutePrimitive);
            }

            const params = new URLSearchParams(decodeURIComponent(currentUrlObj.search));
            
            // Helper function to get array values from URLSearchParams, handling both nice[] and nice[0], nice[1] formats
            const getArrayValues = (baseKey: string): string[] => {
                // First try the standard array format (nice[])
                const standardArray = params.getAll(`${baseKey}[]`);
                if (standardArray.length > 0) {
                    return standardArray;
                }
                
                // If not found, try indexed format (nice[0], nice[1], etc.)
                const indexedValues: { index: number; value: string }[] = [];
                for (const [paramKey, paramValue] of params.entries()) {
                    const match = paramKey.match(new RegExp(`^${baseKey}\\[(\\d+)\\]$`));
                    if (match) {
                        const index = parseInt(match[1] as string, 10);
                        indexedValues.push({ index, value: paramValue });
                    }
                }
                
                // Sort by index and extract values
                indexedValues.sort((a, b) => a.index - b.index);
                return indexedValues.map(item => item.value);
            };
            
            for (const [key, values] of expected.entries()) {
                if (values.length === 0) continue;
                
                let present: string[];
                if (key.endsWith('[]')) {
                    // Handle array parameters
                    const baseKey = key.slice(0, -2); // Remove '[]' suffix
                    present = getArrayValues(baseKey).map(v => {
                        try { return decodeURIComponent(v); } catch { return v; }
                    });
                } else {
                    // Handle regular parameters
                    present = params.getAll(key).map(v => {
                        try { return decodeURIComponent(v); } catch { return v; }
                    });
                }
                
                for (const wanted of values) if (!present.includes(wanted)) return false;
            }
            return true;
        };
        JAVASCRIPT;
    }

    private function generateNamedRoutesJs(array $namedRoutes): string
    {
        if (empty($namedRoutes)) {
            return 'export const namedRoutes: Record<string, string> = {};';
        }

        $routesArray = [];
        foreach ($namedRoutes as $name => $url) {
            $routesArray[] = "    '{$name}': '{$url}'";
        }

        return "export const namedRoutes: Record<string, string> = {\n".implode(",\n", $routesArray)."\n};";
    }

    private function generateCurrentRouteContent(string $content, string $routesImportPath, string $routeTypesJs): string
    {
        $pattern = '/export const currentRoute = \(url\?\: string\) => \{[\s\S]*?\};/';
        $content = preg_replace($pattern, '', $content);

        $buffer = [];
        $buffer[] = "import { namedRoutes, isValidWildcardPattern, checkQueryParams } from '{$routesImportPath}';";
        // Base utilities/content first
        $buffer[] = trim($content);
        $buffer[] = $routeTypesJs;

        $buffer[] = <<<'JAVASCRIPT'
        /**
        * Returns the current URL if called with no arguments, otherwise checks if the current route matches the given name and params.
        *
        * @overload
        * currentRoute(): string
        * @overload
        * currentRoute(name: RouteName, params?: RouteArguments): boolean
        * 
        * Check github page for more details
        * https://github.com/laravel/wayfinder
        *
        */
        export function currentRoute(): string;
        export function currentRoute(name?: RouteName, params?: RouteArguments): boolean;
        export function currentRoute(name?: RouteName, params?: RouteArguments): string | boolean {
            if (name == null) return window.location.href;

            const currentUrl = decodeURI(window.location.href);
            const currentUrlObj = new URL(currentUrl);
            const currentPath = decodeURIComponent(currentUrlObj.pathname.replace(/\/$/, ''));
            const normalize = (url: string) => url.replace(/\/$/, '');

            const replaceRouteParams = (routeUrl: string, routeParams: RouteArguments): string => {
                if (typeof routeParams === 'string' || typeof routeParams === 'number' || typeof routeParams === 'boolean') {
                    return routeUrl.replace(/\{[^}]+\}/, String(routeParams));
                }
                if (typeof routeParams === 'object' && routeParams !== null) {
                    let result = routeUrl;
                    for (const [key, value] of Object.entries(routeParams)) {
                        // Skip arrays - they should be treated as query parameters, not route parameters
                        if (Array.isArray(value)) {
                            continue;
                        }
                        const val = String(value);
                        result = result.replace(new RegExp(`\\{${key}\\}`, 'g'), val);
                        result = result.replace(new RegExp(`\\{${key}\\?\\}`, 'g'), val);
                    }
                    return result.replace(/\/\{[^}]+\?\}/g, '').replace(/\{[^}]+\?\}/g, '');
                }
                return routeUrl;
            };

            const extractParamsFromPath = (currentPath: string, routeTemplate: string): Record<string, string> | null => {
                try {
                    const segments = decodeURIComponent(routeTemplate).split('/').filter((_, i, arr) => !(i === 0 && arr[0] === ''));
                    const paramNames: Array<{ name: string; optional: boolean }> = [];
                    let regexPattern = '';

                    for (const seg of segments) {
                        if (seg.startsWith('{') && seg.endsWith('}')) {
                            const inner = seg.slice(1, -1);
                            const isOptional = inner.endsWith('?');
                            paramNames.push({ name: inner.replace(/\?$/, ''), optional: isOptional });
                            regexPattern += isOptional ? '(?:/([^/]+))?' : '/([^/]+)';
                        } else {
                            regexPattern += '/' + seg.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        }
                    }

                    const regex = new RegExp(`^${regexPattern || '/'}$`);
                    const match = currentPath.match(regex);
                    if (!match) return null;

                    const params: Record<string, string> = {};
                    let groupIndex = 1;
                    for (const meta of paramNames) {
                        const value = match[groupIndex];
                        if (value !== undefined && (value !== '' || !meta.optional)) {
                            params[meta.name] = decodeURIComponent(value);
                        }
                        groupIndex += 1;
                    }
                    return params;
                } catch {
                    return null;
                }
            };

            const matchRoute = (routeName: string, routeUrl: string) => {
                const routePath = decodeURIComponent(routeUrl.replace(/^[a-z]+:\/\/[^/]+/i, '').replace(/\/$/, ''));
                
                // If params contains any arrays, treat them as query parameters only
                if (typeof params === 'object' && params !== null) {
                    const hasArrays = Object.values(params).some(value => Array.isArray(value));
                    if (hasArrays) {
                        // For routes with parameters, if we have arrays, only check query params
                        if (/\{[^}]+\}/.test(routePath)) {
                            return checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                        }
                        // For routes without parameters, if we have arrays, only check query params
                        return checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                    }
                }
                
                if (params === undefined) {
                    const nCurrent = normalize(currentPath);
                    const nRoute = normalize(routePath);
                    if (nCurrent === nRoute) return true;
                    
                    if (/\{[^}]+\}/.test(routePath)) {
                        const extracted = extractParamsFromPath(nCurrent, nRoute);
                        if (extracted !== null) {
                            let reconstructed = routeUrl;
                            for (const [key, value] of Object.entries(extracted)) {
                                const encoded = encodeURIComponent(value);
                                reconstructed = reconstructed.replace(new RegExp(`\\{${key}\\}`, 'g'), encoded);
                                reconstructed = reconstructed.replace(new RegExp(`\\{${key}\\?\\}`, 'g'), encoded);
                            }
                            reconstructed = reconstructed.replace(/\/\{[^}]+\?\}/g, '').replace(/\{[^}]+\?\}/g, '');
                            const reconstructedPath = decodeURIComponent(new URL(reconstructed).pathname.replace(/\/$/, ''));
                            return normalize(nCurrent) === normalize(reconstructedPath);
                        }
                    }
                    return false;
                }

                if (/\{[^}]+\}/.test(routePath)) {
                    const extracted = extractParamsFromPath(normalize(currentPath), normalize(routePath));
                    if (extracted === null) return false;

                    if (typeof params === 'string' || typeof params === 'number' || typeof params === 'boolean') {
                        const firstMatch = routePath.match(/\{([^}]+)\}/);
                        if (!firstMatch || firstMatch[1] === undefined) return false;
                        return extracted[firstMatch[1].replace(/\?$/, '')] === String(params);
                    }

                    if (typeof params === 'object' && params !== null) {
                        for (const [key, value] of Object.entries(params)) {
                            // Skip arrays when checking route parameters - they should be query parameters
                            if (Array.isArray(value)) {
                                continue;
                            }
                            if (namedRoutes[routeName] && (namedRoutes[routeName].includes(`{${key}}`) || namedRoutes[routeName].includes(`{${key}?}`))) {
                                if ((extracted[key] ?? null) !== String(value)) return false;
                            }
                        }
                        return checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                    }
                    return false;
                }

                const urlWithParams = replaceRouteParams(routeUrl, params);
                const pathWithParams = decodeURIComponent(new URL(urlWithParams).pathname.replace(/\/$/, ''));
                const pathMatches = normalize(currentPath) === normalize(pathWithParams);
                
                if (typeof params === 'object' && params !== null) {
                    return pathMatches && checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                }
                return pathMatches;
            };

            // Exact route match
            if (namedRoutes[name]) {
                return matchRoute(name, namedRoutes[name]);
            }

            // Wildcard matching
            if (name.includes('*')) {
                if (!isValidWildcardPattern(name)) return false;

                let matchingRoutes: string[] = [];
                if (name.startsWith('*.')) {
                    const suffix = name.substring(2);
                    matchingRoutes = Object.keys(namedRoutes).filter(route => route.endsWith('.' + suffix));
                } else if (name.endsWith('.*')) {
                    const prefix = name.substring(0, name.length - 2);
                    matchingRoutes = Object.keys(namedRoutes).filter(route => route.startsWith(prefix + '.'));
                }
                
                return matchingRoutes.some(routeName => {
                    const routeUrl = namedRoutes[routeName];
                    if (!routeUrl) return false;
                    
                    if (params === undefined) {
                        const routePath = decodeURIComponent(routeUrl.replace(/^[a-z]+:\/\/[^/]+/i, '').replace(/\/$/, ''));
                        if (normalize(currentPath) === normalize(routePath)) return true;
                        if (/\{[^}]+\}/.test(routePath)) {
                            return extractParamsFromPath(currentPath, routePath) !== null;
                        }
                        return currentPath.startsWith(normalize(routePath));
                    }
                    
                    return matchRoute(routeName, routeUrl);
                });
            }

            return false;
        }
        JAVASCRIPT;

        array_splice($buffer, 1, 0, []);

        return implode("\n\n", $buffer);
    }
}
