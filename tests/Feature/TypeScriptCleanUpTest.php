<?php

namespace Tests\Feature;

use Laravel\Wayfinder\TypeScript;
use PHPUnit\Framework\TestCase;

class TypeScriptCleanUpTest extends TestCase
{
    public function test_multi_line_arguments_with_parentheses_are_collapsed(): void
    {
        $view = implode(PHP_EOL, [
            'export const show = (',
            "    args: { project: import('@/types').Key<'Project', string> } | [project: string],",
            '    options?: RouteQueryOptions,',
            "): RouteDefinition<'get'> => ({",
            '    url: show.url(args, options),',
            "    method: 'get',",
            '})',
        ]);

        $this->assertSame(implode(PHP_EOL, [
            "export const show = (args: { project: import('@/types').Key<'Project', string> } | [project: string], options?: RouteQueryOptions,): RouteDefinition<'get'> => ({",
            '    url: show.url(args, options),',
            "    method: 'get',",
            '})',
        ]), TypeScript::cleanUp($view));
    }
}
