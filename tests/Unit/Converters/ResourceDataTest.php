<?php

namespace Tests\Unit\Converters;

use Illuminate\Routing\Route as BaseRoute;
use Laravel\Ranger\Components\ResourceResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Surveyor\Types\Contracts\Type as TypeContract;
use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Converters\ResourceData;
use Laravel\Wayfinder\Registry\ResultConverter;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use PHPUnit\Framework\TestCase;
use ReflectionNamedType;
use ReflectionProperty;

class ResourceDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! ResultConverter::getRegistry()->hasConverter(TypeScriptConverter::class)) {
            ResultConverter::register(TypeScriptConverter::class);
        }
    }

    public function test_array_payload_is_wrapped(): void
    {
        $this->assertSame(
            '{ data: { id: number } }',
            $this->convert(['id' => Type::int()], wrap: 'data'),
        );
    }

    public function test_array_payload_collection_is_wrapped_as_list(): void
    {
        $this->assertSame(
            '{ data: { id: number }[] }',
            $this->convert(['id' => Type::int()], isCollection: true, wrap: 'data'),
        );
    }

    public function test_unwrapped_array_payload_is_returned_bare(): void
    {
        $this->assertSame(
            '{ id: number }',
            $this->convert(['id' => Type::int()], wrap: null),
        );
    }

    public function test_unwrapped_array_payload_with_additional_is_nested_under_data(): void
    {
        $this->assertSame(
            '{ data: { id: number }, meta: { version: string } }',
            $this->convert(
                ['id' => Type::int()],
                wrap: null,
                additional: ['meta' => Type::array(['version' => Type::string()])],
            ),
        );
    }

    public function test_list_payload_type_renders_as_array_of_items(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ data: { id: number }[] }',
            $this->convert(Type::arrayShape(Type::int(), Type::array(['id' => Type::int()])), wrap: 'data'),
        );
    }

    public function test_keyed_payload_type_renders_as_record(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ data: Record<string, { id: number }> }',
            $this->convert(Type::arrayShape(Type::string(), Type::array(['id' => Type::int()])), wrap: 'data'),
        );
    }

    public function test_union_payload_type_stays_a_union(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ data: { id: number } | { message: string } }',
            $this->convert($this->union(), wrap: 'data'),
        );
    }

    public function test_union_payload_collection_groups_before_list_suffix(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ data: ({ id: number } | { message: string })[] }',
            $this->convert($this->union(), isCollection: true, wrap: 'data'),
        );
    }

    public function test_unwrapped_union_payload_is_returned_bare(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ id: number } | { message: string }',
            $this->convert($this->union(), wrap: null),
        );
    }

    public function test_unwrapped_payload_type_with_additional_is_nested_under_data(): void
    {
        $this->requiresTypedPayloads();

        $this->assertSame(
            '{ data: { id: number } | { message: string }, meta: { version: string } }',
            $this->convert(
                $this->union(),
                wrap: null,
                additional: ['meta' => Type::array(['version' => Type::string()])],
            ),
        );
    }

    protected function convert(array|TypeContract $data, bool $isCollection = false, ?string $wrap = 'data', array $additional = []): ?string
    {
        $route = new Route(
            new BaseRoute(['GET'], 'users', ['uses' => 'App\\Http\\Controllers\\UserController@show']),
            collect(),
            null,
            null,
        );

        return (new ResourceData)->convert(new ResourceResponse(
            resourceClass: 'App\\Http\\Resources\\UserResource',
            data: $data,
            isCollection: $isCollection,
            wrap: $wrap,
            additional: $additional,
        ), $route);
    }

    protected function union(): TypeContract
    {
        return Type::union(
            Type::array(['id' => Type::int()]),
            Type::array(['message' => Type::string()]),
        );
    }

    protected function requiresTypedPayloads(): void
    {
        $type = (new ReflectionProperty(ResourceResponse::class, 'data'))->getType();

        if ($type instanceof ReflectionNamedType) {
            $this->markTestSkipped('Installed laravel/ranger only passes array resource payloads.');
        }
    }
}
