<?php

namespace Tests\Unit\Langs;

use Laravel\Wayfinder\Langs\TypeScript;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    public function test_it_escapes_double_quotes(): void
    {
        $this->assertSame('"say \"hi\""', TypeScript::quote('say "hi"'));
    }

    public function test_it_leaves_single_quotes_alone(): void
    {
        $this->assertSame('"it\'s here"', TypeScript::quote("it's here"));
    }

    public function test_it_escapes_backslashes(): void
    {
        $this->assertSame('"back\\\\slash"', TypeScript::quote('back\slash'));
    }

    public function test_it_escapes_newlines_and_control_characters(): void
    {
        $this->assertSame('"line\nbreak"', TypeScript::quote("line\nbreak"));
        $this->assertSame('"tab\there"', TypeScript::quote("tab\there"));
    }

    public function test_it_escapes_line_separators(): void
    {
        $this->assertSame('"a\\u2028b"', TypeScript::quote("a\u{2028}b"));
        $this->assertSame('"a\\u2029b"', TypeScript::quote("a\u{2029}b"));
    }

    public function test_it_leaves_slashes_and_unicode_unescaped(): void
    {
        $this->assertSame('"Pages/Users/Edit"', TypeScript::quote('Pages/Users/Edit'));
        $this->assertSame('"café"', TypeScript::quote('café'));
    }

    public function test_it_quotes_keys_that_are_not_identifiers(): void
    {
        $this->assertSame('foo', TypeScript::quoteKey('foo'));
        $this->assertSame('"for-sale"', TypeScript::quoteKey('for-sale'));
        $this->assertSame('"say \"hi\""', TypeScript::quoteKey('say "hi"'));
    }
}
