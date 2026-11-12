<?php

namespace Tests\Unit\Registry;

use Laravel\Surveyor\Types;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use PHPUnit\Framework\TestCase;

class TypeScriptConverterTest extends TestCase
{
    protected TypeScriptConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        if (! ResultConverter::getRegistry()->hasConverter(TypeScriptConverter::class)) {
            ResultConverter::register(TypeScriptConverter::class);
        }

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

    public function test_nullable_resource_response_keeps_null(): void
    {
        $response = $this->resourceResponse()->nullable();

        $this->assertSame('{ id: number } | null', $this->converter->convert($response));
    }

    public function test_nullable_resource_collection_response_keeps_null(): void
    {
        $response = $this->resourceResponse(isCollection: true)->nullable();

        $this->assertSame('{ id: number }[] | null', $this->converter->convert($response));
    }

    public function test_resource_response_is_not_nullable_by_default(): void
    {
        $this->assertSame('{ id: number }', $this->converter->convert($this->resourceResponse()));
    }

    protected function resourceResponse(bool $isCollection = false): Types\Entities\ResourceResponse
    {
        return new Types\Entities\ResourceResponse(
            'App\\Http\\Resources\\UserResource',
            Types\Type::array(['id' => Types\Type::int()]),
            $isCollection,
        );
    }
}
