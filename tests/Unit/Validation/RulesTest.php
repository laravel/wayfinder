<?php

namespace Tests\Unit\Validation;

use Illuminate\Support\Collection;
use Laravel\Ranger\Validation\Rule;
use Laravel\Wayfinder\Validation\Rules;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('uploadRules')]
    public function test_rules_that_only_pass_for_an_upload_are_a_file(array $rule): void
    {
        $this->assertSame('File', $this->resolve($rule));
    }

    /**
     * @return array<string, array{array{string, list<string>}}>
     */
    public static function uploadRules(): array
    {
        return [
            'file' => [['File', []]],
            'image' => [['Image', []]],
            'mimes' => [['Mimes', ['pdf', 'docx']]],
            'mimetypes' => [['Mimetypes', ['application/pdf']]],
            'extensions' => [['Extensions', ['zip']]],
            'dimensions' => [['Dimensions', ['min_width=100']]],
        ];
    }

    public function test_a_nullable_upload_is_a_file_or_null(): void
    {
        $this->assertSame('File | null', $this->resolve(['Nullable', []], ['Image', []]));
    }

    public function test_size_rules_alone_do_not_make_a_file(): void
    {
        $this->assertSame('string', $this->resolve(['Max', ['5120']]));
    }

    protected function resolve(array ...$rules): string
    {
        return (new Rules(new Collection(array_map(fn (array $rule) => new Rule($rule), $rules))))->resolveFieldType();
    }
}
