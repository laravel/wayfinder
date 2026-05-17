<?php

namespace Tests\Unit\Langs\TypeScript;

use Laravel\Wayfinder\Langs\TypeScript;
use PHPUnit\Framework\TestCase;

class TypeObjectBuilderTest extends TestCase
{
    public function test_type_object_does_not_use_shorthand_for_matching_key_and_value(): void
    {
        $type = TypeScript::objectToTypeObject([
            'number' => 'number',
        ], false);

        $this->assertSame('{ number: number }', (string) $type);
    }

    public function test_object_record_uses_shorthand_for_matching_key_and_value(): void
    {
        $object = TypeScript::objectToRecord([
            'url' => 'url',
        ], false);

        $this->assertStringContainsString('url', (string) $object);
        $this->assertStringNotContainsString('url: url', (string) $object);
    }
}
