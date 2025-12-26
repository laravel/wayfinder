<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Wayfinder\Langs\TypeScript;
use Stringable;

class ObjectBuilder implements Stringable
{
    protected bool $inline = false;

    protected ?string $satisfies = null;

    /**
     * @var list<ObjectKeyValueBuilder>
     */
    protected $keyValuePairs = [];

    public function key(string $key): ObjectKeyValueBuilder
    {
        foreach ($this->keyValuePairs as $index => $keyValue) {
            if ($keyValue->getKey() === $key) {
                unset($this->keyValuePairs[$index]);
            }
        }

        $keyValue = new ObjectKeyValueBuilder($key);

        $this->keyValuePairs[] = $keyValue;

        return $keyValue;
    }

    public function satisfies(string $type): static
    {
        $this->satisfies = $type;

        return $this;
    }

    public function inline(): static
    {
        $this->inline = true;

        return $this;
    }

    public function __toString(): string
    {
        $object = '{';

        if (! $this->inline) {
            $object .= PHP_EOL;
        }

        $pairs = $this->inline
            ? $this->keyValuePairs
            : array_map(
                fn ($pair) => TypeScript::indent($pair),
                $this->keyValuePairs,
            );

        $glue = $this->inline ? ', ' : ','.PHP_EOL;
        $object .= implode($glue, $pairs);
        $object .= $this->inline ? ' ' : ','.PHP_EOL;
        $object .= '}';

        if ($this->satisfies) {
            $object .= ' satisfies '.$this->satisfies;
        }

        return $object;
    }
}
