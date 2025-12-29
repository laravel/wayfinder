<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\BroadcastEvent;
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
        $namespaceRoots = $namespacedEvents
            ->map(fn (BroadcastEvent $event) => str($event->name)->before('\\')->toString())
            ->unique();
        $grouped = $events->groupBy(fn (BroadcastEvent $event) => $event->name);

        $namespacedEvents->each(
            fn (BroadcastEvent $event) => TypeScript::addFqnToNamespaced(
                $event->name,
                TypeScript::type(
                    str($event->name)->afterLast('\\'),
                    TypeScript::objectToTypeObject($event->data->value, false),
                )
                    ->referenceClass($event->className, $event->filePath())
                    ->export(),
            ),
        );

        $results[] = new Result('broadcast-events.ts', $this->fileContent($grouped));

        if ($echoPackageContent = $this->echoFileContent($grouped, $namespaceRoots)) {
            $results[] = new Result('echo-broadcast-events.d.ts', $echoPackageContent);
        }

        return $results;
    }

    protected function echoFileContent(Collection $grouped, Collection $namespaceRoots): ?string
    {
        $echoPackage = collect(['@laravel/echo-vue', '@laravel/echo-react'])
            ->first(fn ($package) => Npm::isInstalled($package));

        if (! $echoPackage) {
            return null;
        }

        $eventsInterface = $grouped->map(
            fn ($events, $key) => (string) TypeScript::objectKeyValue(
                $this->toEventName($key),
                TypeScript::objectToTypeObject($events->first()->data->value, false)
            ),
        );

        $content = [];

        if ($namespaceRoots->isNotEmpty()) {
            $content[] = (string) Imports::create()->add('./types', $namespaceRoots->all());
            $content[] = '';
        }

        return implode(PHP_EOL, $content).PHP_EOL.TypeScript::module(
            $echoPackage,
            TypeScript::interface('Events', $eventsInterface->implode(PHP_EOL))
        );
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

    protected function toEventName(string $name)
    {
        return '.'.str_replace('\\', '.', $name);
    }
}
