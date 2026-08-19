<?php

namespace Tests\Unit\Validation;

use Illuminate\Support\Collection;
use Laravel\Ranger\Validation\Rule;
use Laravel\Wayfinder\Validation\Rules;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    public function test_array_rule_without_keys_is_an_unknown_list(): void
    {
        $this->assertSame('unknown[]', $this->resolve(['Array', []]));
    }

    public function test_array_rule_with_keys_is_an_object_of_optional_keys(): void
    {
        $this->assertSame(
            '{ theme?: unknown, timezone?: unknown }',
            $this->resolve(['Array', ['theme', 'timezone']]),
        );
    }

    public function test_array_rule_keys_that_are_not_identifiers_are_quoted(): void
    {
        $this->assertSame(
            '{ "font-size"?: unknown, "line-height"?: unknown }',
            $this->resolve(['Array', ['font-size', 'line-height']]),
        );
    }

    protected function resolve(array $rule): string
    {
        return (new Rules(new Collection([new Rule($rule)])))->resolveFieldType();
    }
}
