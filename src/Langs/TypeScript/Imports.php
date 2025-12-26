<?php

namespace Laravel\Wayfinder\Langs\TypeScript;

use InvalidArgumentException;
use Laravel\Wayfinder\Langs\TypeScript;
use Stringable;

class Imports implements Stringable
{
    public array $imports = [];

    public static function create(): self
    {
        return new self;
    }

    public function addImport(Import $import): self
    {
        $this->imports[$import->from] ??= [];

        $exists = collect($this->imports[$import->from])->first(
            fn ($i) => $i->import === $import->import && $i->isType() === $import->isType() && $i->isDefault() === $import->isDefault(),
        );

        if (! $exists) {
            $this->imports[$import->from][] = $import;
        }

        return $this;
    }

    public function addImports(array $imports): self
    {
        foreach ($imports as $import) {
            $this->addImport($import);
        }

        return $this;
    }

    public function addWildcard(
        string $from,
        string $import,
        bool $safe = false,
        bool $type = false,
        bool $default = false,
    ): self {
        return $this->add($from, '*', $import, $safe, $type, $default);
    }

    public function add(
        string $from,
        string|array $imports,
        ?string $alias = null,
        bool $safe = false,
        bool $type = false,
        bool $default = false,
    ): self {
        $imports = is_array($imports) ? $imports : [$imports];

        $this->imports[$from] ??= [];

        foreach ($imports as $import) {
            $exists = collect($this->imports[$from])->first(
                fn ($i) => $i->import === $import && $i->isType() === $type && $i->isDefault() === $default,
            );

            if (! $exists) {
                $this->imports[$from][] = (new Import($import, $from))
                    ->safe($safe)
                    ->type($type)
                    ->wildcard(in_array('*', $imports))
                    ->default($default)
                    ->alias($alias);
            }
        }

        return $this;
    }

    public function addSafe(
        string $from,
        string|array $imports,
    ) {
        return $this->add(
            from: $from,
            imports: $imports,
            safe: true,
        );
    }

    public function addSafeMethod(
        string $from,
        string $import,
        string $suffix = 'Method',
        bool $default = false,
    ) {
        return $this->add(
            from: $from,
            imports: TypeScript::safeMethod($import, $suffix),
            default: $default,
        );
    }

    public function addDefault(
        string $from,
        string $import,
        ?string $alias = null,
        bool $safe = false,
    ) {
        return $this->add(
            from: $from,
            imports: $import,
            default: true,
            alias: $alias,
            safe: $safe,
        );
    }

    public function addSafeType(
        string $from,
        string|array $imports,
    ) {
        return $this->add(
            from: $from,
            imports: $imports,
            safe: true,
            type: true,
        );
    }

    public function addType(
        string $from,
        string|array $imports,
    ) {
        return $this->add(
            from: $from,
            imports: $imports,
            safe: false,
            type: true,
        );
    }

    public function get(string $import)
    {
        foreach ($this->imports as $from => $items) {
            foreach ($items as $item) {
                if ($import === $item->import) {
                    return $item->importedName();
                }
            }
        }

        throw new InvalidArgumentException("Unknown import, could not retrieve: {$import} from {$from}");
    }

    public function asLines(): array
    {
        $lines = [];

        foreach ($this->imports as $from => $imports) {
            $default = null;
            $named = null;

            $collection = collect($imports);

            [$wildcardImports, $regularImports] = $collection->partition(fn ($i) => $i->isWildcard());
            $typeImports = $regularImports->filter(fn ($i) => $i->isType())->map(fn ($i) => $i->get());
            $defaultImports = $regularImports->filter(fn ($i) => $i->isDefault())->map(fn ($i) => $i->get());
            $namedImports = $regularImports->filter(fn ($i) => $i->isNamed())->map(fn ($i) => $i->get());
            $wildcardImports = $wildcardImports->map(fn ($i) => $i->get());

            $allNamedImports = $namedImports->sort()->merge(
                $typeImports->sort()->map(fn ($i) => "type {$i}")
            );

            if ($wildcardImports->isNotEmpty()) {
                $lines[] = sprintf('import %s from "%s";', $wildcardImports->sort()->implode(', '), $from);
            }

            if ($defaultImports->isNotEmpty()) {
                $default = $defaultImports->first();
            }

            if ($allNamedImports->isNotEmpty()) {
                $named = '{ '.$allNamedImports->implode(', ').' }';
            }

            if ($default || $named) {
                $lines[] = sprintf(
                    'import %s from "%s";',
                    implode(', ', array_filter([$default, $named])),
                    $from,
                );
            }
        }

        return $lines;
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->asLines());
    }
}
