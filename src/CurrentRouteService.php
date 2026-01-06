<?php

namespace Laravel\Wayfinder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;

class CurrentRouteService
{
    public function __construct(
        private Filesystem $files,
        private UrlGenerator $url,
    ) {
        //
    }

    public function generate(
        string $originalContent,
        Collection $routes,
        string $basePath,
    ): string {
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
                $uri = trim($route->uri(), "'\"");

                $fullUrl = '/'.ltrim($uri, '/');
                $namedRoutes[$name] = rtrim($fullUrl, '/') ?: '/';
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
        if (empty($routeNames)) {
            return [];
        }

        // Convert route names to segments
        $routeSegments = array_map(fn ($route) => explode('.', $route), $routeNames);

        // Count all pattern types
        $prefixCounts = [];
        $suffixCounts = [];
        $middleCounts = [];

        foreach ($routeSegments as $parts) {
            $partCount = count($parts);

            // Generate prefixes (skip single segments)
            for ($i = 1; $i < $partCount; $i++) {
                $prefix = implode('.', array_slice($parts, 0, $i));
                $prefixCounts[$prefix] = ($prefixCounts[$prefix] ?? 0) + 1;
            }

            // Generate suffixes
            for ($i = 1; $i < $partCount; $i++) {
                $suffix = implode('.', array_slice($parts, $i));
                $suffixCounts[$suffix] = ($suffixCounts[$suffix] ?? 0) + 1;
            }

            // Generate middle patterns (e.g., post.*.show from post.user.show)
            for ($i = 1; $i < $partCount - 1; $i++) {
                for ($j = $i + 1; $j < $partCount; $j++) {
                    $before = implode('.', array_slice($parts, 0, $i));
                    $after = implode('.', array_slice($parts, $j));
                    $middlePattern = "$before.*.$after";
                    $middleCounts[$middlePattern] = ($middleCounts[$middlePattern] ?? 0) + 1;
                }
            }
        }

        // Build wildcards from patterns with 2+ occurrences
        $wildcards = [];

        foreach ($prefixCounts as $prefix => $count) {
            if ($count >= 2) {
                $wildcards[] = "$prefix.*";
            }
        }

        foreach ($suffixCounts as $suffix => $count) {
            if ($count >= 2) {
                $wildcards[] = "*.$suffix";
            }
        }

        foreach ($middleCounts as $pattern => $count) {
            if ($count >= 2) {
                $wildcards[] = $pattern;
            }
        }

        return array_unique($wildcards);
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

        // Generate arrays for different wildcard types
        $prefixWildcards = [];
        $suffixWildcards = [];
        $middleWildcards = [];

        foreach ($wildcards as $wildcard) {
            if (str_starts_with($wildcard, '*.')) {
                $suffixWildcards[] = "'".substr($wildcard, 2)."'";
            } elseif (str_ends_with($wildcard, '.*')) {
                $prefixWildcards[] = "'".substr($wildcard, 0, -2)."'";
            } elseif (str_contains($wildcard, '.*.')) {
                $middleWildcards[] = "'".$wildcard."'";
            }
        }

        $prefixArray = empty($prefixWildcards) ? '[]' : '['.implode(', ', $prefixWildcards).']';
        $suffixArray = empty($suffixWildcards) ? '[]' : '['.implode(', ', $suffixWildcards).']';
        $middleArray = empty($middleWildcards) ? '[]' : '['.implode(', ', $middleWildcards).']';

        return <<<JAVASCRIPT
        /**
         * Validates if a wildcard pattern matches existing routes
         * This function is auto-generated based on your application's routes
         */
        export const isValidWildcardPattern = (pattern: string): boolean => {
            const prefixWildcards: string[] = {$prefixArray};
            const suffixWildcards: string[] = {$suffixArray};
            const middleWildcards: string[] = {$middleArray};
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
            
            if (pattern.includes('.*.')) {
                return middleWildcards.includes(pattern) && 
                    routeNames.some(route => {
                        const parts = route.split('.');
                        const patternParts = pattern.split('.');
                        if (parts.length !== patternParts.length) return false;
                        
                        for (let i = 0; i < parts.length; i++) {
                            if (patternParts[i] !== '*' && patternParts[i] !== parts[i]) {
                                return false;
                            }
                        }
                        return true;
                    });
            }
            
            return false;
        };
        JAVASCRIPT;
    }

    private function generateCheckQueryParams(): string
    {
        return <<<'JAVASCRIPT'
        // Validates query parameters of the current URL against provided RouteArguments
        export const checkQueryParams = (
            currentUrlObj: URL,
            name: string,
            routeParams: RouteArguments,
            namedRoutes: Record<string, string>,
        ): boolean => {
            if (routeParams === null || typeof routeParams !== 'object') return true;

            // Convert values to query string format
            const toQueryValue = (v: RoutePrimitive) => v === true ? '1' : v === false ? '0' : String(v);
            
            // Check if a key is a route parameter (not query parameter)
            const isRouteParam = (key: string): boolean => {
                const routeTemplate = namedRoutes[name] ?? '';
                return routeTemplate.includes(`{${key}}`) || routeTemplate.includes(`{${key}?}`);
            };

            // Build expected query parameters
            const expectedParams = new Map<string, string[]>();
            
            const addParam = (key: string, value: RoutePrimitive | RoutePrimitive[] | RouteObject | null | undefined) => {
                if (value == null) return;
                
                if (Array.isArray(value)) {
                    const arrayKey = `${key}[]`;
                    const values = expectedParams.get(arrayKey) ?? [];
                    value.forEach(item => values.push(toQueryValue(item)));
                    expectedParams.set(arrayKey, values);
                    return;
                }
                
                if (typeof value === 'object') {
                    Object.entries(value).forEach(([subKey, subValue]) => {
                        const fullKey = key ? `${key}[${subKey}]` : subKey;
                        addParam(fullKey, subValue as RoutePrimitive);
                    });
                    return;
                }
                
                const values = expectedParams.get(key) ?? [];
                values.push(toQueryValue(value));
                expectedParams.set(key, values);
            };

            // Process route parameters, skipping non-array route params
            Object.entries(routeParams).forEach(([key, value]) => {
                if (isRouteParam(key) && !Array.isArray(value)) return;
                addParam(key, value as RoutePrimitive);
            });

            const currentParams = new URLSearchParams(decodeURIComponent(currentUrlObj.search));
            
            // Get array values from URL (handles both nice[] and nice[0], nice[1] formats)
            const getArrayValues = (baseKey: string): string[] => {
                // Try standard array format first
                const standardArray = currentParams.getAll(`${baseKey}[]`);
                if (standardArray.length > 0) return standardArray;
                
                // Fallback to indexed format
                const indexedValues: { index: number; value: string }[] = [];
                for (const [paramKey, paramValue] of currentParams.entries()) {
                    const match = paramKey.match(new RegExp(`^${baseKey}\\[(\\d+)\\]$`));
                    if (match) {
                        indexedValues.push({ 
                            index: parseInt(match[1] as string, 10), 
                            value: paramValue 
                        });
                    }
                }
                
                return indexedValues
                    .sort((a, b) => a.index - b.index)
                    .map(item => item.value);
            };
            
            // Validate all expected parameters exist in current URL
            for (const [key, expectedValues] of expectedParams.entries()) {
                if (expectedValues.length === 0) continue;
                
                const currentValues = key.endsWith('[]') 
                    ? getArrayValues(key.slice(0, -2)).map(v => {
                        try { return decodeURIComponent(v); } catch { return v; }
                    })
                    : currentParams.getAll(key).map(v => {
                        try { return decodeURIComponent(v); } catch { return v; }
                    });
                
                // Check if all expected values are present
                if (!expectedValues.every(expected => currentValues.includes(expected))) {
                    return false;
                }
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
        * Returns the current URL if called with no arguments, otherwise checks if the current route matches the given name or controller route and params.
        *
        * @overload
        * currentRoute(): string
        * @overload
        * currentRoute(name: RouteName, params?: RouteArguments): boolean
        * @overload
        * currentRoute(routeDefinition: RouteDefinition<Method>): boolean
        * @overload
        * currentRoute(url: string): boolean
        * 
        * Check github page for more details
        * @see {@link https://github.com/laravel/wayfinder}
        *
        */
        export function currentRoute(): string;
        export function currentRoute(name: RouteName, params?: RouteArguments): boolean;
        export function currentRoute(routeDefinition: RouteDefinition<Method>): boolean;
        export function currentRoute(url: string): boolean;
        export function currentRoute(name?: RouteName | RouteDefinition<Method> | string, params?: RouteArguments): string | boolean {
            if (name == null) return window.location.href;

            const currentUrl = decodeURI(window.location.href);
            const currentUrlObj = new URL(currentUrl);
            const currentPath = decodeURIComponent(currentUrlObj.pathname.replace(/\/$/, ''));
            const normalize = (url: string) => url.replace(/\/$/, '');
            
            // Helper to parse URL and extract path and query params
            const parseUrl = (url: string): { path: string; queryParams: URLSearchParams } => {
                let fullUrl = url;
                if (url.startsWith('/') && !url.startsWith('//')) {
                    fullUrl = window.location.origin || 'http://localhost' + url;
                }
                const urlObj = new URL(fullUrl);
                return {
                    path: decodeURIComponent(urlObj.pathname.replace(/\/$/, '')),
                    queryParams: urlObj.searchParams
                };
            };

            // Helper to compare query params
            const queryParamsMatch = (expected: URLSearchParams): boolean => {
                for (const [key, value] of expected.entries()) {
                    const currentValues = currentUrlObj.searchParams.getAll(key);
                    if (!currentValues.includes(value)) {
                        return false;
                    }
                }
                return true;
            };

            // Helper to check if params contain arrays
            const hasArrayParams = (params: RouteArguments): boolean => {
                return typeof params === 'object' && params !== null && 
                       Object.values(params).some(value => Array.isArray(value));
            };

            // Helper to check if route has parameters
            const hasRouteParams = (routePath: string): boolean => /\{[^}]+\}/.test(routePath);

            // Helper to replace route parameters
            const replaceRouteParams = (routeUrl: string, routeParams: RouteArguments): string => {
                if (typeof routeParams === 'string' || typeof routeParams === 'number' || typeof routeParams === 'boolean') {
                    return routeUrl.replace(/\{[^}]+\}/, String(routeParams));
                }
                if (typeof routeParams === 'object' && routeParams !== null) {
                    let result = routeUrl;
                    for (const [key, value] of Object.entries(routeParams)) {
                        if (Array.isArray(value)) continue; // Skip arrays - query params only
                        const val = String(value);
                        result = result.replace(new RegExp(`\\{${key}\\}`, 'g'), val);
                        result = result.replace(new RegExp(`\\{${key}\\?\\}`, 'g'), val);
                    }
                    return result.replace(/\/\{[^}]+\?\}/g, '').replace(/\{[^}]+\?\}/g, '');
                }
                return routeUrl;
            };

            // Helper to extract parameters from path
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

            // Helper to match path with parameters
            const matchPathWithParams = (currentPath: string, routePath: string, routeUrl: string): boolean => {
                const extracted = extractParamsFromPath(normalize(currentPath), normalize(routePath));
                if (extracted === null) return false;

                let reconstructed = routeUrl;
                for (const [key, value] of Object.entries(extracted)) {
                    const encoded = encodeURIComponent(value);
                    reconstructed = reconstructed.replace(new RegExp(`\\{${key}\\}`, 'g'), encoded);
                    reconstructed = reconstructed.replace(new RegExp(`\\{${key}\\?\\}`, 'g'), encoded);
                }
                reconstructed = reconstructed.replace(/\/\{[^}]+\?\}/g, '').replace(/\{[^}]+\?\}/g, '');
                const reconstructedPath = decodeURIComponent(reconstructed.replace(/\/$/, ''));
                return normalize(currentPath) === normalize(reconstructedPath);
            };

            // Helper to validate route parameters
            const validateRouteParams = (routeName: string, extracted: Record<string, string>): boolean => {
                if (typeof params === 'string' || typeof params === 'number' || typeof params === 'boolean') {
                    const firstMatch = namedRoutes[routeName]?.match(/\{([^}]+)\}/);
                    if (!firstMatch || firstMatch[1] === undefined) return false;
                    return extracted[firstMatch[1].replace(/\?$/, '')] === String(params);
                }

                if (typeof params === 'object' && params !== null) {
                    for (const [key, value] of Object.entries(params)) {
                        if (Array.isArray(value)) continue; // Skip arrays - query params only
                        if (namedRoutes[routeName] && (namedRoutes[routeName].includes(`{${key}}`) || namedRoutes[routeName].includes(`{${key}?}`))) {
                            if ((extracted[key] ?? null) !== String(value)) return false;
                        }
                    }
                    return checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                }
                return false;
            };

            // Handle RouteDefinition object (from controller) or direct URL string
            if (
                (typeof name === 'object' && name !== null && 'url' in name) ||
                (typeof name === 'string' && (name.startsWith('/') || name.startsWith('http://') || name.startsWith('https://')))
            ) {
                const toParse = typeof name === 'object' ? name.url : name;
                const { path, queryParams } = parseUrl(toParse);
                const pathMatches = normalize(currentPath) === normalize(path);
                return pathMatches && queryParamsMatch(queryParams);
            }

            // Main route matching function
            const matchRoute = (routeName: string, routeUrl: string) => {
                const routePath = decodeURIComponent(routeUrl.replace(/\/$/, ''));
                
                // Handle arrays - treat as query parameters only
                if (hasArrayParams(params)) {
                    return checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                }
                
                if (params === undefined) {
                    const nCurrent = normalize(currentPath);
                    const nRoute = normalize(routePath);
                    if (nCurrent === nRoute) return true;
                    
                    if (hasRouteParams(routePath)) {
                        return matchPathWithParams(currentPath, routePath, routeUrl);
                    }
                    return false;
                }

                if (hasRouteParams(routePath)) {
                    const extracted = extractParamsFromPath(normalize(currentPath), normalize(routePath));
                    if (extracted === null) return false;
                    return validateRouteParams(routeName, extracted);
                }

                const urlWithParams = replaceRouteParams(routeUrl, params);
                const pathWithParams = decodeURIComponent(urlWithParams.replace(/\/$/, ''));
                const pathMatches = normalize(currentPath) === normalize(pathWithParams);
                
                if (typeof params === 'object' && params !== null) {
                    return pathMatches && checkQueryParams(currentUrlObj, routeName, params, namedRoutes);
                }
                return pathMatches;
            };

            // Handle named routes
            if (typeof name === 'string' && namedRoutes[name]) {
                return matchRoute(name, namedRoutes[name]);
            }

            // Wildcard matching
            if (typeof name === 'string' && name.includes('*')) {
                if (!isValidWildcardPattern(name)) return false;

                let matchingRoutes: string[] = [];
                const namedRoutesKeys = Object.keys(namedRoutes);
                
                if (name.startsWith('*.')) {
                    const suffix = name.substring(2);
                    matchingRoutes = namedRoutesKeys.filter(route => route.endsWith('.' + suffix));
                } else if (name.endsWith('.*')) {
                    const prefix = name.substring(0, name.length - 2);
                    matchingRoutes = namedRoutesKeys.filter(route => route.startsWith(prefix + '.'));
                } else if (name.includes('.*.')) {
                    matchingRoutes = namedRoutesKeys.filter(route => {
                        const parts = route.split('.');
                        const patternParts = name.split('.');
                        if (parts.length !== patternParts.length) return false;
                        
                        return patternParts.every((part, index) => part === '*' || part === parts[index]);
                    });
                }
                
                return matchingRoutes.some(routeName => {
                    const routeUrl = namedRoutes[routeName];
                    if (!routeUrl) return false;
                    
                    if (params === undefined) {
                        const routePath = decodeURIComponent(routeUrl.replace(/\/$/, ''));
                        if (normalize(currentPath) === normalize(routePath)) return true;
                        if (hasRouteParams(routePath)) {
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
