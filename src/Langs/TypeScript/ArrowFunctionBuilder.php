<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Surveyor\Types\Type;
use Laravel\Wayfinder\Langs\Concerns\CanExport;
use Laravel\Wayfinder\Langs\Concerns\HasMeta;
use Laravel\Wayfinder\Langs\TypeScript;
use Stringable;

class ArrowFunctionBuilder implements Stringable
{
    use CanExport, HasMeta;

    protected $arguments = [];

    protected $returnType = '';

    protected $body = '';

    public function __construct(protected ?string $name = null)
    {
        //
    }

    public function argument(string $name, string|array|Type $types, bool $optional = false): static
    {
        $this->arguments[] = [
            $name,
            is_array($types) ? $types : [$types],
            $optional,
        ];

        return $this;
    }

    public function returnType(string $returnType): static
    {
        $this->returnType = $returnType;

        return $this;
    }

    public function body(string|array $body): static
    {
        $this->body = is_array($body) ? implode(PHP_EOL.PHP_EOL, $body) : $body;

        return $this;
    }

    public function formattedArguments(): array
    {
        return array_map($this->formatArgument(...), $this->arguments);
    }

    public function formatArgument(array $argument): string
    {
        [$name, $types, $optional] = $argument;

        $arg = $name;

        if ($optional) {
            $arg .= '?';
        }

        return $arg.': '.implode(' | ', $types);
    }

    public function __toString(): string
    {
        $block = $this->meta();

        if ($block !== '') {
            $block .= PHP_EOL;
        }

        if ($this->name) {
            $block .= $this->exportFormatted();
            $block .= 'const '.$this->name.' = ';
        }

        $block .= '('.implode(', ', $this->formattedArguments()).')';

        if ($this->returnType) {
            $block .= ': '.$this->returnType;
        }

        $block .= ' => ';

        if (str_starts_with($this->body, '{') && str_ends_with($this->body, '}')) {
            $block .= '('.$this->body.')';
        } elseif ($this->body !== '') {
            $block .= '{'.PHP_EOL.TypeScript::indent($this->body).PHP_EOL.'}';
        } else {
            $block .= '{}';
        }

        return $block;
    }
}
