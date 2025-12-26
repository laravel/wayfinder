<?php

namespace Laravel\Wayfinder\Registry;

use InvalidArgumentException;
use Laravel\Surveyor\Types\Contracts\Type;

class ConverterRegistry
{
    protected array $converters = [];

    public function register(string $converter): void
    {
        $instance = app($converter);

        if (! ($instance instanceof ConverterInterface)) {
            throw new InvalidArgumentException("Converter {$converter} must implement ".ConverterInterface::class);
        }

        if ($this->hasConverter($converter)) {
            throw new InvalidArgumentException("Converter {$converter} already registered");
        }

        $this->converters[$converter] = $instance;
    }

    public function convert(Type $result, string $converter): string
    {
        if (! $this->hasConverter($converter)) {
            throw new InvalidArgumentException("No converter registered for format: {$converter}");
        }

        return $this->converters[$converter]->convert($result);
    }

    public function hasConverter(string $format): bool
    {
        return isset($this->converters[$format]);
    }
}
