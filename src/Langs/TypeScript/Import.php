<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use Laravel\Wayfinder\Langs\TypeScript;

use function Illuminate\Filesystem\join_paths;

class Import
{
    protected bool $safe = false;

    protected bool $type = false;

    protected bool $default = false;

    protected bool $wildcard = false;

    protected ?string $alias = null;

    public function __construct(
        public readonly string $import,
        public readonly string $from,
    ) {
        //
    }

    public static function relativePathFromFile(string $path, ?string $suffix = null): string
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        $count = substr_count($path, DIRECTORY_SEPARATOR);

        $final = '.' . DIRECTORY_SEPARATOR . ltrim(str_repeat(DIRECTORY_SEPARATOR . '..', $count), DIRECTORY_SEPARATOR);

        if ($suffix) {
            return join_paths($final, $suffix);
        }

        return $final;
    }

    public function safe(bool $safe = true): self
    {
        $this->safe = $safe;

        return $this;
    }

    public function alias(?string $alias = null): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function type(bool $type = true): self
    {
        $this->type = $type;

        return $this;
    }

    public function wildcard(bool $wildcard = true): self
    {
        $this->wildcard = $wildcard;

        return $this;
    }

    public function default(bool $default = true): self
    {
        $this->default = $default;

        return $this;
    }

    public function isWildcard(): bool
    {
        return $this->wildcard;
    }

    public function isNamed(): bool
    {
        return ! $this->isType() && ! $this->isDefault();
    }

    public function isType(): bool
    {
        return $this->type;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function get(): string
    {
        $importedName = $this->importedName();

        if ($this->isDefault() || $importedName === $this->import) {
            return $importedName;
        }

        return sprintf('%s as %s', $this->import, $importedName);
    }

    public function importedName(): string
    {
        if ($this->alias) {
            return $this->alias;
        }

        if ($this->safe) {
            return TypeScript::safeImport($this->import);
        }

        return $this->import;
    }
}
