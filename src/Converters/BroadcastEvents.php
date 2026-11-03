<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\BroadcastEvent;
use Laravel\Surveyor\Types\ArrayType;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Langs\TypeScript\Imports;
use Laravel\Wayfinder\Langs\TypeScript\ObjectKeyValueBuilder;
use Laravel\Wayfinder\Results\Result;
use Laravel\Wayfinder\Support\Npm;

class BroadcastEvents extends Converter
{
    /**
     * @param  Collection<BroadcastEvent>  $events
     */
    public function convert(Collection $events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        $results = [];

        $namespacedEvents = $events->filter(fn ($event) => str_contains($event->name, '\\'));
        $grouped = $events->groupBy(fn (BroadcastEvent $event) => $event->name);

        $namespacedEvents->each(
            fn (BroadcastEvent $event) => TypeScript::addFqnToNamespaced(
                $event->name,
                TypeScript::type(
                    str($event->name)->afterLast('\\'),
                    $this->payloadType($event),
                )
                    ->referenceClass($event->className, $event->filePath())
                    ->export(),
            ),
        );

        $results[] = new Result('broadcast-events.ts', $this->fileContent($grouped));

        if ($echoPackageContent = $this->echoFileContent($grouped, $namespacedEvents)) {
            $results[] = new Result('echo-broadcast-events.d.ts', $echoPackageContent);
        }

        return $results;
    }

    protected function echoFileContent(Collection $grouped, Collection $namespacedEvents): ?string
    {
        $echoPackage = Npm::findFirstInstalledPackage(['@laravel/echo-vue', '@laravel/echo-react', '@laravel/echo-svelte']);

        if (! $echoPackage) {
            return null;
        }

        $eventsInterface = $grouped->map(
            fn ($events, $key) => (string) TypeScript::objectKeyValue(
                $this->toEventName($key),
                $this->payloadType($events->first()),
            ),
        );

        $namespaceRoots = $this->referencedRoots($eventsInterface->implode(PHP_EOL));

        $content = [];

        if ($namespaceRoots !== []) {
            $content[] = (string) Imports::create()->add('./types', $namespaceRoots);
            $content[] = '';
        }

        // Echo's adapters read a global Events interface, and a global can only
        // be augmented from a module, hence the empty export.
        return implode(PHP_EOL, [
            ...$content,
            'declare global {',
            TypeScript::interface('Events', $eventsInterface->implode(PHP_EOL)),
            '}',
            '',
            'export {};',
        ]);
    }

    protected function fileContent(Collection $grouped): string
    {
        $undotted = $grouped
            ->mapWithKeys(fn ($events, $key) => [
                str_replace('\\', '.', $key) => $events,
            ])->undot();

        $content = [
            TypeScript::literalUnion(
                'BroadcastEvent',
                $grouped->keys()->map($this->toEventName(...)),
            )->export(),
            '',
        ];

        $content[] = TypeScript::constant('BroadcastEvents', $this->toObject($undotted))->asConst()->export();

        return implode(PHP_EOL, $content);
    }

    protected function toObject(Collection|array $undotted): string
    {
        $obj = TypeScript::object();

        foreach ($undotted as $key => $subEvents) {
            $keyValue = $obj->key($key);

            if ($subEvents instanceof Collection) {
                $keyValue->value(TypeScript::quote($this->toEventName($subEvents->first()->name)));
                $this->withLinks($keyValue, $subEvents);
            } else {
                $keyValue->value($this->toObject($subEvents));
            }
        }

        return (string) $obj;
    }

    protected function withLinks(ObjectKeyValueBuilder $block, Collection $events): ObjectKeyValueBuilder
    {
        foreach ($events as $event) {
            $block->referenceClass($event->className, $event->filePath());
        }

        return $block;
    }

    protected function payloadType(BroadcastEvent $event): string
    {
        if (! $event->data instanceof ArrayType) {
            return 'Record<string, unknown>';
        }

        return (string) TypeScript::objectToTypeObject($event->data->value, false);
    }

    /**
     * The namespaces a payload refers to, which have to be imported from types.d.ts.
     *
     * @return list<string>
     */
    protected function referencedRoots(string $types): array
    {
        preg_match_all('/\x{27EA}wayfinder:([A-Za-z_][A-Za-z0-9_]*)\./u', $types, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Echo prefixes its namespace and swaps dots for backslashes unless the
     * name starts with a dot, so the escaped class name (or broadcastAs()
     * name) is the only form that matches whatever namespace the client set.
     */
    protected function toEventName(string $name): string
    {
        return '.'.$name;
    }
}
