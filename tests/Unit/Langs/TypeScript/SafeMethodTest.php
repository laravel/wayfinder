<?php

namespace Tests\Unit\Langs\TypeScript;

use Laravel\Wayfinder\Langs\TypeScript;
use PHPUnit\Framework\TestCase;

class SafeMethodTest extends TestCase
{
    public function test_reserved_keywords_are_suffixed(): void
    {
        $keywords = [
            'await',
            'enum',
            'implements',
            'interface',
            'let',
            'package',
            'private',
            'protected',
            'public',
            'static',
            'yield',
        ];

        foreach ($keywords as $keyword) {
            $this->assertSame(
                $keyword.'Method',
                TypeScript::safeMethod($keyword, 'Method'),
                "Expected [{$keyword}] to be suffixed.",
            );
        }
    }
}
