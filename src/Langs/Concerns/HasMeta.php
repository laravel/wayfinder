<?php

namespace Laravel\Wayfinder\Langs\Concerns;

use Laravel\Wayfinder\Langs\TypeScript;

trait HasMeta
{
    protected $meta = [];

    public function referenceMethod(string $class, string $method, ?string $path = null): static
    {
        $class = $this->prepClass($class);

        if ($path) {
            $path = $this->prepPath($path);
            $this->link("{$class}::{$method}", $path);
        } else {
            $this->meta[] = sprintf('@see %s::%s', $class, $method);
        }

        return $this;
    }

    public function referenceClass(string $class, ?string $path = null): static
    {
        $class = $this->prepClass($class);

        if ($path) {
            $path = $this->prepPath($path);
            $this->link($class, $path);
        } else {
            $this->meta[] = '@see ' . $class;
        }

        return $this;
    }

    public function link(string $text, ?string $url = null): static
    {
        $this->meta[] = sprintf('@see [%s](%s)', $text, $url ?? $text);

        return $this;
    }

    public function referenceFile(string $path, ?int $lineNumber = null): static
    {
        $this->meta[] = sprintf('@see %s:%d', str($path)->start(DIRECTORY_SEPARATOR)->toString(), $lineNumber);

        return $this;
    }

    public function annotation(string $key, string $value): static
    {
        $this->meta[] = sprintf('@%s %s', $key, $value);

        return $this;
    }

    protected function meta(): string
    {
        if (count($this->meta) === 0) {
            return '';
        }

        return TypeScript::dockblock($this->meta);
    }

    protected function prepClass(string $class): string
    {
        return str($class)->start('\\')->toString();
    }

    protected function prepPath(string $path): string
    {
        return str($path)->start(DIRECTORY_SEPARATOR)->toString();
    }
}
