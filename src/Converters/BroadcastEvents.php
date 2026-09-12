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

        if ($echoPackageContent = $this->echoFileContent($grouped)) {
            $results[] = new Result('echo-broadcast-events.d.ts', $echoPackageContent);
        }

        return $results;
    }

    protected function echoFileContent(Collection $grouped): ?string
    {
        $echoPackage = Npm::findFirstInstalledPackage(['@laravel/echo-vue', '@laravel/echo-react']);

        if (! $echoPackage) {
            return null;
        }

        $eventPayloads = $grouped->map(
            fn ($events) => (string) TypeScript::objectToTypeObject($events->first()->data->value, false),
        );

        $eventsInterface = $eventPayloads->map(
            fn ($payload, $key) => (string) TypeScript::objectKeyValue(
                $this->toEventName($key),
                $payload,
            ),
        );

        // Ignore JSON-quoted payload keys when discovering type references.
        $unquotedPayloads = preg_replace('/"(?:[^"\\\\]|\\\\.)*"/', '', $eventPayloads->implode(PHP_EOL));

        preg_match_all('/(?<!\.)([A-Z][a-zA-Z0-9]*)(?=\.[A-Z])/', $unquotedPayloads, $matches);

        $imports = Imports::create()->addSideEffect($echoPackage);

        if (count($matches[0]) > 0) {
            $imports->add('./types', $matches[0]);
        }

        return $imports.PHP_EOL.PHP_EOL.TypeScript::module(
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
        return '.'.$name;
    }
}
