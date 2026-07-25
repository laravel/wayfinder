<?php

namespace Tests\Unit\Registry;

use Laravel\Surveyor\Types;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use PHPUnit\Framework\TestCase;

class TypeScriptConverterTest extends TestCase
{
    protected TypeScriptConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new TypeScriptConverter;
    }

    public function test_array_shape_with_unknown_key_produces_record_type(): void
    {
        $arrayShape = Types\Type::arrayShape(Types\Type::mixed(), Types\Type::mixed());

        $result = $this->converter->convert($arrayShape);

        $this->assertSame('Record<string, unknown>', $result);
    }

    public function test_array_shape_with_number_key_produces_array_type(): void
    {
        $arrayShape = Types\Type::arrayShape(Types\Type::int(), Types\Type::string());

        $result = $this->converter->convert($arrayShape);

        $this->assertSame('string[]', $result);
    }

    public function test_array_shape_with_string_key_produces_record_type(): void
    {
        $arrayShape = Types\Type::arrayShape(Types\Type::string(), Types\Type::int());

        $result = $this->converter->convert($arrayShape);

        $this->assertSame('Record<string, number>', $result);
    }

    public function test_array_type_produces_array_type(): void
    {
        $arrayType = Types\Type::array([]);

        $result = $this->converter->convert($arrayType);

        $this->assertSame('unknown[]', $result);
    }

    public function test_json_cast_produces_record_type(): void
    {
        $arrayShape = Types\Type::arrayShape(Types\Type::mixed(), Types\Type::mixed());

        $result = $this->converter->convert($arrayShape);

        $this->assertSame('Record<string, unknown>', $result);
    }

    public function test_array_cast_produces_array_type(): void
    {
        $arrayType = Types\Type::array([]);

        $result = $this->converter->convert($arrayType);

        $this->assertSame('unknown[]', $result);
    }
}
