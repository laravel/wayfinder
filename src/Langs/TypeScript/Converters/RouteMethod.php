<?php

namespace Laravel\Wayfinder\Langs\TypeScript\Converters;

use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Support\Verb;
use Laravel\Wayfinder\Langs\TypeScript;

class RouteMethod
{
    protected string $name;

    protected bool $hasParameters;

    protected bool $allOptional;

    protected array $argTypes;

    public function __construct(
        protected Route $route,
        protected bool $withForm,
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
            $this->formVariant(),
            ...$this->formVerbVariants(),
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

        $urlArgs = $this->hasParameters ? ['args', 'options'] : ['options'];

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
            $func->argument('args', $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument('options', 'RouteQueryOptions', true);

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
        $def->satisfies('RouteDefinition<'.$verbs.'>');

        return "{$this->name}.definition = {$def}";
    }

    protected function url(): string
    {
        $func = TypeScript::arrowFunction();

        if ($this->hasParameters) {
            $func->argument('args', $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument('options', 'RouteQueryOptions', true);

        $body = [];

        if ($this->hasParameters) {
            if ($this->route->parameters()->count() === 1) {
                $body[] = <<<TS
                if (typeof args === "string" || typeof args === "number") {
                    args = { {$this->route->parameters()->first()->name}: args }
                }
                TS;

                if ($this->route->parameters()->first()->key) {
                    $body[] = <<<TS
                    if (typeof args === "object" && !Array.isArray(args) && "{$this->route->parameters()->first()->key}" in args) {
                        args = { {$this->route->parameters()->first()->name}: args.{$this->route->parameters()->first()->key} }
                    }
                    TS;
                }
            }

            $argsArrayObject = TypeScript::object();

            foreach ($this->route->parameters() as $i => $parameter) {
                $argsArrayObject->key($parameter->name)->value("args[{$i}]");
            }

            $body[] = <<<TS
            if (Array.isArray(args)) {
                args = {$argsArrayObject}
            }
            TS;

            $body[] = 'args = applyUrlDefaults(args)';

            if ($this->route->parameters()->where('optional')->isNotEmpty()) {
                $optionalParams = $this->route
                    ->parameters()
                    ->where('optional')
                    ->pluck('name')
                    ->toJson();

                $body[] = "validateParameters(args, {$optionalParams})";
            }

            $parsedArgsObject = TypeScript::object();

            foreach ($this->route->parameters() as $parameter) {
                $keyVal = $parsedArgsObject->key($parameter->name);

                if ($parameter->key) {
                    $val = sprintf(
                        'typeof args%s.%s === "object" ? args.%s.%s : args%s.%s',
                        $this->allOptional ? '?' : '',
                        $parameter->name,
                        $parameter->name,
                        $parameter->key ?? 'id',
                        $this->allOptional ? '?' : '',
                        $parameter->name
                    );
                } else {
                    $val = sprintf('args%s.%s', $this->allOptional ? '?' : '', $parameter->name);
                }

                if ($parameter->default !== null) {
                    $val = sprintf('(%s) ?? "%s"', $val, $parameter->default);
                }

                $keyVal->value($val);
            }

            $body[] = TypeScript::constant('parsedArgs', $parsedArgsObject);
        }

        $return = "return {$this->name}.definition.url";

        if ($this->hasParameters) {
            $urlReplace = [];

            foreach ($this->route->parameters() as $parameter) {
                $urlReplace[] = sprintf(
                    '.replace("%s", parsedArgs.%s%s.toString()%s)',
                    $parameter->placeholder,
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

        $return .= ' + queryParams(options)';

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
            $func->argument('args', $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument('options', 'RouteQueryOptions', true);
        $func->returnType('RouteDefinition<"'.$verb->actual.'">');

        $body = TypeScript::object();

        $urlArgs = $this->hasParameters ? ['args', 'options'] : ['options'];

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
            $func->argument('args', $this->collectArgTypes(), $this->allOptional);
        }

        $func->argument('options', 'RouteQueryOptions', true);
        $func->returnType('RouteFormDefinition<"'.$verb->formSafe.'">');

        $body = TypeScript::object();

        $urlArgs = [];

        if ($this->hasParameters) {
            $urlArgs[] = 'args';
        }

        if ($verb->formSafe === $verb->actual) {
            $urlArgs[] = 'options';
        } else {
            $urlArgs[] = 'formSafeOptions("'.strtolower($verb->actual).'", options)';
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
