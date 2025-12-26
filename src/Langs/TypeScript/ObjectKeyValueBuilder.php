<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Wayfinder\Langs\TypeScript;
use Stringable;

class ObjectKeyValueBuilder implements Stringable
{
    protected ?string $value = null;

    protected bool $optional = false;

    protected bool $quote = false;

    protected bool $rawKey = false;

    protected bool $spread = false;

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

    public function __toString(): string
    {
        $key = $this->rawKey ? $this->key : TypeScript::quoteKey($this->key);

        if ($this->spread) {
            $key = "...{$key}";
        }

        if ($this->value !== null) {
            $value = $this->quote ? TypeScript::quote($this->value) : $this->value;
        }

        if ($this->value === null) {
            return $key;
        }

        if (! $this->optional && ! str_contains($key, '"') && $key === $value) {
            return $key;
        }

        if ($this->optional) {
            return $key.'?: '.$value;
        }

        return $key.': '.$value;
    }
}
