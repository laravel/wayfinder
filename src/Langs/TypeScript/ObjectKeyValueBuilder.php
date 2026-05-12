<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Wayfinder\Langs\Concerns\HasMeta;
use Laravel\Wayfinder\Langs\TypeScript;
use Stringable;

class ObjectKeyValueBuilder implements Stringable
{
    use HasMeta;

    protected ?string $value = null;

    protected bool $optional = false;

    protected bool $quote = false;

    protected bool $rawKey = false;

    protected bool $spread = false;

    protected bool $shorthand = true;

    public function __construct(protected string $key)
    {
        //
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function spread(): static
    {
        $this->spread = true;
        $this->rawKey = true;

        return $this;
    }

    public function rawKey(bool $raw = true): static
    {
        $this->rawKey = $raw;

        return $this;
    }

    public function value(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function optional($optional = true): static
    {
        $this->optional = $optional;

        return $this;
    }

    public function quote(bool $quote = true): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function shorthand(bool $shorthand = true): static
    {
        $this->shorthand = $shorthand;

        return $this;
    }

    public function __toString(): string
    {
        $value = $this->value;
        $block = $this->meta();

        if ($block !== '') {
            $block .= PHP_EOL;
        }

        $key = $this->rawKey ? $this->key : TypeScript::quoteKey($this->key);

        if ($this->spread) {
            $key = "...{$key}";
        }

        $block .= $key;

        if ($value === null) {
            return $block;
        }

        $value = $this->quote ? TypeScript::quote($value) : $value;

        if ($this->shorthand && ! $this->optional && ! str_contains($key, '"') && $key === $value) {
            return $block;
        }

        if ($this->optional) {
            return $block.'?: '.$value;
        }

        return $block.': '.$value;
    }
}
