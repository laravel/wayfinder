<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Wayfinder\Langs\Concerns\CanExport;
use Laravel\Wayfinder\Langs\Concerns\HasMeta;
use Stringable;

class VariableBuilder implements Stringable
{
    use CanExport, HasMeta;

    protected $backtick = false;

    protected $asConst = false;

    public function __construct(protected string $content)
    {
        //
    }

    public function backtick(): static
    {
        $this->backtick = true;

        return $this;
    }

    /**
     * Append ' as const' to the block.
     */
    public function asConst(): static
    {
        $this->asConst = true;

        return $this;
    }

    public function __toString(): string
    {
        $block = $this->meta();

        if ($block !== '') {
            $block .= PHP_EOL;
        }

        $block .= $this->exportFormatted();

        if ($this->backtick) {
            $this->content = "`{$this->content}`";
        }

        if ($this->asConst) {
            $this->content .= ' as const';
        }

        return $block.$this->content;
    }
}
