<?php

namespace Tests\Unit;

use Laravel\Ranger\RangerServiceProvider;
use Laravel\Surveyor\SurveyorServiceProvider;
use Laravel\Surveyor\Types\Entities\View;
use Laravel\Surveyor\Types\NeverType;
use Laravel\Surveyor\Types\ObjectType;
use Laravel\Surveyor\Types\TemplateTagType;
use Laravel\Surveyor\Types\Type;
use Laravel\Surveyor\Types\UnionType;
use Laravel\Surveyor\Types\VoidType;
use Laravel\Wayfinder\Registry\TypeScriptConverter;
use Laravel\Wayfinder\WayfinderServiceProvider;
use Orchestra\Testbench\TestCase;

class TypeScriptConverterTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [SurveyorServiceProvider::class, RangerServiceProvider::class, WayfinderServiceProvider::class];
    }

    public function test_every_surveyor_type_converts_without_throwing(): void
    {
        $converter = new TypeScriptConverter;

        $this->assertSame('never', $converter->convert(new NeverType));
        $this->assertSame('null', $converter->convert(new VoidType));
        $this->assertSame('Record<string, unknown>', $converter->convert(new ObjectType));
        $this->assertSame('number', $converter->convert(new TemplateTagType('T', Type::int(), null, null, null)));
        $this->assertSame('unknown', $converter->convert(new TemplateTagType('T', null, null, null, null)));
        $this->assertIsString($converter->convert(new View('welcome', Type::array([]))));
    }

    public function test_a_union_with_unknown_is_unknown(): void
    {
        $converter = new TypeScriptConverter;

        $this->assertSame('unknown', $converter->convert(new UnionType([Type::mixed(), Type::bool(false)])));
        $this->assertSame('string | null', $converter->convert(Type::union(Type::string(), Type::null())));
    }
}
