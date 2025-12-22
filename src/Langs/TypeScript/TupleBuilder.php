<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Stringable;

class TupleBuilder implements Stringable
{
    protected $items = [];

    public function item(string|array $type, ?string $name = null): static
    {
        $this->items[] = [is_array($type) ? $type : [$type], $name];

        return $this;
    }

    public function __toString(): string
    {
        $items = [];

        foreach ($this->items as $item) {
            $i = '';

            if ($item[1]) {
                $i .= $item[1] . ': ';
            }

            $items[] = $i . implode(' | ', $item[0]);
        }

        return '[ ' . implode(', ', $items) . ' ]';
    }
}
