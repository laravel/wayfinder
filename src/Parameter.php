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
        return match ($type) {
            'int' => 'number',
            'integer' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'bigint' => 'number',
            'number' => 'number',
            default => 'string',
        };
    }
}
