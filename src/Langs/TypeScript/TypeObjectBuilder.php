<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Stringable;

class TypeObjectBuilder implements Stringable
{
    protected bool $inline = false;

    /**
     * @var list<ObjectKeyValueBuilder>
     */
    protected $keyValuePairs = [];

    public function inline(): static
    {
        $this->inline = true;

        return $this;
    }

    public function key(string $key): ObjectKeyValueBuilder
    {
        $keyValue = new ObjectKeyValueBuilder($key);

        $this->keyValuePairs[] = $keyValue;

        return $keyValue;
    }

    public function __toString(): string
    {
        return '{ '.implode(', ', $this->keyValuePairs).' }';
    }
}
