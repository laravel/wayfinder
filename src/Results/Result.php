<?php

namespace Laravel\Wayfinder\Results;

use Laravel\Wayfinder\Langs\TypeScript\Imports;

class Result
{
    public function __construct(
        public readonly string $name,
        public readonly string $content,
        public ?Imports $imports = null,
    ) {
        $this->imports ??= Imports::create();
    }

    public function content(): string
    {
        $importLines = $this->imports->asLines();

        if (count($importLines) === 0) {
            return $this->content;
        }

        return implode(PHP_EOL . PHP_EOL, [
            implode(PHP_EOL, $importLines),
            $this->content,
        ]);
    }
}
