<?php

namespace Laravel\Wayfinder;

use Illuminate\Support\Reflector;
use ReflectionParameter;

class Parameter
{
    public string $placeholder;

    public string $types;

    public function __construct(
        public string $name,
        public bool $optional,
        public ?string $key,
        public ?string $default,
        public ?ReflectionParameter $bound = null,
    ) {
        $this->placeholder = $optional ? "{{$name}?}" : "{{$name}}";

        $this->types = implode(' | ', $this->resolveTypes());
    }

    protected function resolveTypes(): array
    {
        if (! $this->bound) {
            return ['string', 'number'];
        }

        $model = Reflector::getParameterClassName($this->bound);

        if (! $model) {
            return ['string', 'number'];
        }

        [$type, $this->key] = BindingResolver::resolveTypeAndKey($model, $this->key);

        if (! $type) {
            return ['string', 'number'];
        }

        return [$this->typeToTypeScript($type)];
    }

    protected function typeToTypeScript($type)
    {
        $mapping = [
            'number' => [
                'int',
                'integer',
                'bigint',
                'int4',
                'int8',
                'serial',
                'bigserial',
                'bigint',
                'number',
                'float',
                'double',
                'decimal',
            ],
            'string' => ['string', 'text', 'varchar', 'char', 'json', 'jsonb'],
            'boolean' => ['bool', 'boolean'],
        ];

        foreach ($mapping as $tsType => $types) {
            if (in_array($type, $types)) {
                return $tsType;
            }
        }

        return 'string';
    }

    public function safeName(): string
    {
        return TypeScript::safeMethod($this->name, 'Param');
    }
}
