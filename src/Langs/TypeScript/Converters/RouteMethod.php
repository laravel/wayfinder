<?php

namespace Laravel\Wayfinder\Langs\TypeScript\Converters;

use Laravel\Ranger\Components\InertiaResponse;
use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Support\Verb;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Langs\TypeScript\ObjectBuilder;

class RouteMethod
{
    protected string $name;

    protected bool $hasParameters;

    protected bool $allOptional;

    protected array $argTypes;

    protected string $argsParam;

    protected string $optionsParam;

    protected string $parsedArgsParam;

    public function __construct(
        protected Route $route,
        protected bool $withForm,
        protected bool $withInertiaComponent = false,
        protected bool $named = false,
        protected array $relatedRoutes = [],
        protected bool $tmpMethod = false,
    ) {
        $this->name = TypeScript::safeMethod($this->jsMethod($route), 'Method');

        if ($this->tmpMethod) {
            $this->name = $this->tmpMethod($route);
        }

        $this->hasParameters = $route->parameters()->isNotEmpty();
        $this->allOptional = $route->parameters()->every->optional;

        $this->argsParam = $this->name === 'args' ? 'routeArgs' : 'args';
        $this->optionsParam = $this->name === 'options' ? 'routeOptions' : 'options';
        $this->parsedArgsParam = $this->name === 'parsedArgs' ? 'routeParsedArgs' : 'parsedArgs';
    }

    public function controllerMethod(): string
    {
        if (count($this->relatedRoutes) > 1) {
            return $this->multiRouteControllerMethod();
        }

        return implode(PHP_EOL.PHP_EOL, array_map(fn ($line) => trim($line), [
            $this->base(),
            $this->definition(),
            $this->url(),
            ...$this->verbs(),
            $this->withComponentMethod(),
            $this->formVariant(),
            ...$this->formVerbVariants(),
            $this->withComponentFormMethod(),
            $this->withForm ? "{$this->name}.form = {$this->name}Form" : '',
        ]));
    }

    public function computedMethods(): array
    {
        if ($this->named) {
            return [
                str($this->route->name())->afterLast('.')->toString() => $this->name,
            ];
        }

        return [
            $this->route->method() => $this->name,
        ];
    }

    protected function multiRouteControllerMethod(): string
    {
        $output = [];

        foreach ($this->relatedRoutes as $route) {
            $routeMethod = new static(
                route: $route,
                withForm: $this->withForm,
                withInertiaComponent: $this->withInertiaComponent,
                named: $this->named,
                tmpMethod: true,
            );

            $output[] = $routeMethod->controllerMethod();
        }

        $object = TypeScript::object();

        foreach ($this->relatedRoutes as $route) {
            $object->key($route->uri())->value($this->tmpMethod($route));
        }

        $const = TypeScript::constant($this->name, $object)->export($this->named || ! $this->route->hasInvokableController());

        $output[] = (string) $const;

        return implode(PHP_EOL.PHP_EOL, $output);
    }

    protected function base(): string
    {
        $object = TypeScript::object();

        $urlArgs = $this->hasParameters ? [$this->argsParam, $this->optionsParam] : [$this->optionsParam];

        $object
            ->key('url')
            ->value($this->name.'.url('.implode(', ', $urlArgs).')');
        $object
            ->key('method')
            ->value($this->route->verbs()->first()->actual)
            ->quote();

        $func = TypeScript::arrowFunction($this->name)
            ->export($this->named || ! $this->route->hasInvokableController())
            ->returnType('RouteDefinition<"'.$this->route->verbs()->first()->actual.'">')
            ->body($object);

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);

        $this->addDockblock($func);

        return $func;
    }

    protected function addDockblock($block): void
    {
        if (! str_contains($this->route->controller(), '\\Closure')) {
            $block->referenceMethod($this->route->controller(), $this->route->method());
        }

        $block->referenceFile($this->route->controllerPath(), $this->route->methodLineNumber());

        foreach ($this->route->parameters() as $parameter) {
            if ($parameter->default !== null) {
                $block->annotation('param', "{$parameter->name} - Default: {$parameter->default}");
            }
        }

        $block->annotation('route', '"'.$this->route->uri().'"');
    }

    protected function collectArgTypes(): array
    {
        if (isset($this->argTypes)) {
            return $this->argTypes;
        }

        $typeObject = TypeScript::typeObject();
        $tuple = TypeScript::tuple();

        foreach ($this->route->parameters() as $parameter) {
            $types = array_map(fn ($type) => TypeScript::fromSurveyorType($type), $parameter->types);
            $baseTypes = $types;

            if ($parameter->key) {
                $paramTypeObject = TypeScript::typeObject();
                $paramTypeObject->key($parameter->key)->value(TypeScript::union($baseTypes));
                $baseTypes[] = (string) $paramTypeObject;
            }

            $tuple->item($baseTypes, TypeScript::safeMethod($parameter->name, 'Param'));
            $typeObject->key($parameter->name)->value(TypeScript::union($baseTypes))->optional($parameter->optional);
        }

        $argTypes = [$typeObject, $tuple];

        if ($this->route->parameters()->count() === 1) {
            array_push($argTypes, ...$types);

            if ($paramTypeObject ?? false) {
                $argTypes[] = $paramTypeObject;
            }
        }

        return $this->argTypes = $argTypes;
    }

    protected function definition(): string
    {
        $verbs = $this->route->verbs()->pluck('actual')->toJson();

        $def = TypeScript::object();
        $def->key('methods')->value($verbs);
        $def->key('url')->value($this->route->uri())->quote();

        $this->addInertiaComponent($def);

        $componentType = $this->inertiaComponentType();

        $def->satisfies($componentType
            ? 'RouteDefinition<'.$verbs.', '.$componentType.'>'
            : 'RouteDefinition<'.$verbs.'>'
        );

        return "{$this->name}.definition = {$def}";
    }

    protected function url(): string
    {
        $func = TypeScript::arrowFunction();

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);

        $body = [];

        if ($this->hasParameters) {
            if ($this->route->parameters()->count() === 1) {
                $body[] = <<<TS
                if (typeof {$this->argsParam} === "string" || typeof {$this->argsParam} === "number") {
                    {$this->argsParam} = { {$this->route->parameters()->first()->name}: {$this->argsParam} }
                }
                TS;

                if ($this->route->parameters()->first()->key) {
                    $body[] = <<<TS
                    if (typeof {$this->argsParam} === "object" && !Array.isArray({$this->argsParam}) && "{$this->route->parameters()->first()->key}" in {$this->argsParam}) {
                        {$this->argsParam} = { {$this->route->parameters()->first()->name}: {$this->argsParam}.{$this->route->parameters()->first()->key} }
                    }
                    TS;
                }
            }

            $argsArrayObject = TypeScript::object();

            foreach ($this->route->parameters() as $i => $parameter) {
                $argsArrayObject->key($parameter->name)->value("{$this->argsParam}[{$i}]");
            }

            $body[] = <<<TS
            if (Array.isArray({$this->argsParam})) {
                {$this->argsParam} = {$argsArrayObject}
            }
            TS;

            $body[] = "{$this->argsParam} = applyUrlDefaults({$this->argsParam})";

            if ($this->route->parameters()->where('optional')->isNotEmpty()) {
                $optionalParams = $this->route
                    ->parameters()
                    ->where('optional')
                    ->pluck('name')
                    ->toJson();

                $body[] = "validateParameters({$this->argsParam}, {$optionalParams})";
            }

            $parsedArgsObject = TypeScript::object();

            foreach ($this->route->parameters() as $parameter) {
                $keyVal = $parsedArgsObject->key($parameter->name);

                if ($parameter->key) {
                    $val = sprintf(
                        'typeof %s%s.%s === "object" ? %s.%s.%s : %s%s.%s',
                        $this->argsParam,
                        $this->allOptional ? '?' : '',
                        $parameter->name,
                        $this->argsParam,
                        $parameter->name,
                        $parameter->key ?? 'id',
                        $this->argsParam,
                        $this->allOptional ? '?' : '',
                        $parameter->name
                    );
                } else {
                    $val = sprintf('%s%s.%s', $this->argsParam, $this->allOptional ? '?' : '', $parameter->name);
                }

                if ($parameter->default !== null) {
                    $val = sprintf('(%s) ?? "%s"', $val, $parameter->default);
                }

                $keyVal->value($val);
            }

            $body[] = TypeScript::constant($this->parsedArgsParam, $parsedArgsObject);
        }

        $return = "return {$this->name}.definition.url";

        if ($this->hasParameters) {
            $urlReplace = [];

            foreach ($this->route->parameters() as $parameter) {
                $urlReplace[] = sprintf(
                    '.replace("%s", %s.%s%s.toString()%s)',
                    $parameter->placeholder,
                    $this->parsedArgsParam,
                    $parameter->name,
                    $parameter->optional ? '?' : '',
                    $parameter->optional ? " ?? ''" : ''
                );
            }

            $urlReplace[] = '.replace(/\/+$/, "")';

            $urlReplace = implode(
                PHP_EOL,
                array_map(fn ($line) => TypeScript::indent($line), $urlReplace),
            );

            $return .= PHP_EOL.$urlReplace;
        }

        $return .= " + queryParams({$this->optionsParam})";

        $body[] = $return;

        $func->body($body);

        $block = TypeScript::block("{$this->name}.url = {$func}");

        $this->addDockblock($block);

        return $block;
    }

    protected function verbs(): array
    {
        return $this->route->verbs()->map($this->verbMethod(...))->toArray();
    }

    protected function formVerbVariants(): array
    {
        if (! $this->withForm) {
            return [];
        }

        return $this->route->verbs()->map($this->formVerbMethod(...))->toArray();
    }

    protected function verbMethod(Verb $verb): string
    {
        $func = TypeScript::arrowFunction();

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);
        $func->returnType('RouteDefinition<"'.$verb->actual.'">');

        $body = TypeScript::object();

        $urlArgs = $this->hasParameters ? [$this->argsParam, $this->optionsParam] : [$this->optionsParam];

        $body
            ->key('url')
            ->value($this->name.'.url('.implode(', ', $urlArgs).')');
        $body
            ->key('method')
            ->value($verb->actual)
            ->quote();

        $func->body($body);

        $block = TypeScript::block("{$this->name}.{$verb->actual} = {$func}");

        $this->addDockblock($block);

        return $block;
    }

    protected function formVerbMethod(Verb $verb): string
    {
        $func = $this->formVariantForVerb($verb);

        $block = TypeScript::block("{$this->name}Form.{$verb->actual} = {$func}");

        $this->addDockblock($block);

        return $block;
    }

    protected function formVariantForVerb(Verb $verb): string
    {
        $func = TypeScript::arrowFunction();

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);
        $func->returnType('RouteFormDefinition<"'.$verb->formSafe.'">');

        $body = TypeScript::object();

        $urlArgs = [];

        if ($this->hasParameters) {
            $urlArgs[] = $this->argsParam;
        }

        if ($verb->formSafe === $verb->actual) {
            $urlArgs[] = $this->optionsParam;
        } else {
            $urlArgs[] = 'formSafeOptions("'.strtolower($verb->actual).'", '.$this->optionsParam.')';
        }

        $body
            ->key('action')
            ->value($this->name.'.url('.implode(', ', $urlArgs).')');

        $body
            ->key('method')
            ->value($verb->formSafe)
            ->quote();

        $func->body($body);

        return $func;
    }

    protected function formVariant(): string
    {
        if (! $this->withForm) {
            return '';
        }

        $func = $this->formVariantForVerb($this->route->verbs()->first());

        $block = TypeScript::constant("{$this->name}Form", $func);

        $this->addDockblock($block);

        return $block;
    }

    protected function tmpMethod(Route $route): string
    {
        return $this->jsMethod($route).hash('xxh128', $route->uri());
    }

    protected function withComponentMethod(): string
    {
        if (! $this->withInertiaComponent) {
            return '';
        }

        $component = $this->inertiaComponent();

        if ($component === null) {
            return '';
        }

        $components = collect($this->route->possibleResponses())
            ->filter(fn ($response) => $response instanceof InertiaResponse)
            ->map(fn (InertiaResponse $response) => $response->component)
            ->unique()
            ->values();

        $isMulti = $components->count() > 1;

        $func = TypeScript::arrowFunction();

        if ($isMulti) {
            $unionType = $components->map(fn ($c) => TypeScript::quote($c))->implode(' | ');
            $func->argument('component', $unionType);
        }

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);

        $urlArgs = $this->hasParameters ? [$this->argsParam, $this->optionsParam] : [$this->optionsParam];
        $callArgs = implode(', ', $urlArgs);

        if ($isMulti) {
            $body = "{ ...{$this->name}({$callArgs}), component }";
        } else {
            $body = "{ ...{$this->name}({$callArgs}), component: {$component} }";
        }

        $func->body($body);

        $block = TypeScript::block("{$this->name}.withComponent = {$func}");

        $this->addDockblock($block);

        return $block;
    }

    protected function withComponentFormMethod(): string
    {
        if (! $this->withInertiaComponent || ! $this->withForm) {
            return '';
        }

        $component = $this->inertiaComponent();

        if ($component === null) {
            return '';
        }

        $components = collect($this->route->possibleResponses())
            ->filter(fn ($response) => $response instanceof InertiaResponse)
            ->map(fn (InertiaResponse $response) => $response->component)
            ->unique()
            ->values();

        $isMulti = $components->count() > 1;

        $verb = $this->route->verbs()->first();

        $func = TypeScript::arrowFunction();

        if ($isMulti) {
            $unionType = $components->map(fn ($c) => TypeScript::quote($c))->implode(' | ');
            $func->argument('component', $unionType);
        }

        if ($this->hasParameters) {
            $func->argument($this->argsParam, $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument($this->optionsParam, 'RouteQueryOptions', true);

        $urlArgs = [];

        if ($this->hasParameters) {
            $urlArgs[] = $this->argsParam;
        }

        if ($verb->formSafe === $verb->actual) {
            $urlArgs[] = $this->optionsParam;
        } else {
            $urlArgs[] = 'formSafeOptions("'.strtolower($verb->actual).'", '.$this->optionsParam.')';
        }

        $callArgs = implode(', ', $urlArgs);

        if ($isMulti) {
            $body = "{ ...{$this->name}Form({$callArgs}), component }";
        } else {
            $body = "{ ...{$this->name}Form({$callArgs}), component: {$component} }";
        }

        $func->body($body);

        $block = TypeScript::block("{$this->name}Form.withComponent = {$func}");

        $this->addDockblock($block);

        return $block;
    }

    protected function addInertiaComponent(ObjectBuilder $object): void
    {
        if (! $this->withInertiaComponent) {
            return;
        }

        $component = $this->inertiaComponent();

        if ($component !== null) {
            $object->key('component')->value($component);
        }
    }

    protected function inertiaComponent(): ?string
    {
        $components = $this->inertiaComponents();

        if ($components->isEmpty()) {
            return null;
        }

        if ($components->count() === 1) {
            return TypeScript::quote($components->first());
        }

        return TypeScript::objectToRecord($components->mapWithKeys(fn ($c) => [$c => $c]));
    }

    protected function inertiaComponentType(): ?string
    {
        if (! $this->withInertiaComponent) {
            return null;
        }

        $components = $this->inertiaComponents();

        if ($components->isEmpty()) {
            return null;
        }

        return $components->count() === 1 ? 'string' : 'Record<string, string>';
    }

    protected function inertiaComponents()
    {
        return collect($this->route->possibleResponses())
            ->filter(fn ($response) => $response instanceof InertiaResponse)
            ->map(fn (InertiaResponse $response) => $response->component)
            ->unique()
            ->values();
    }

    protected function jsMethod(Route $route): string
    {
        if ($this->named) {
            return str($route->name())->afterLast('.')->toString();
        }

        return $route->hasInvokableController()
            ? str($route->controller())->afterLast('\\')->toString()
            : $route->method();
    }
}
