<?php

use Laravel\Wayfinder\Langs\TypeScript;

test('type object does not use shorthand for matching key and value', function () {
    $type = TypeScript::objectToTypeObject([
        'number' => 'number',
    ], false);

    expect((string) $type)->toBe('{ number: number }');
});

test('object record uses shorthand for matching key and value', function () {
    $object = TypeScript::objectToRecord([
        'url' => 'url',
    ], false);

    expect((string) $object)
        ->toContain('url')
        ->not->toContain('url: url');
});
