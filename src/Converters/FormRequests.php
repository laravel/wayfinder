<?php

namespace Laravel\Wayfinder\Converters;

use Illuminate\Support\Collection;
use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Components\Validator;
use Laravel\Wayfinder\Langs\TypeScript;
use Laravel\Wayfinder\Validation\Rules;
use ReflectionClass;

class FormRequests extends Converter
{
    protected const DEFAULT = 'Record<string, unknown>';

    protected array $named = [];

    protected array $controllers = [];

    protected array $nullValidators = [];

    public function add(?Validator $validator, Route $route): void
    {
        if ($validator === null) {
            $this->nullValidators[$this->routeControllerRequestKey($route)] ??= [];
            $this->nullValidators[$this->routeControllerRequestKey($route)][] = $route;

            return;
        }

        if ($route->hasController()) {
            $this->controllers[$this->routeControllerRequestKey($route)] ??= [];
            $this->controllers[$this->routeControllerRequestKey($route)][] = [$validator, $route];
        }
    }

    public function convert(): void
    {
        foreach ($this->named as $info) {
            $validator = $info[0][0];

            $block = TypeScript::addFqnToNamespaced(
                $validator->name,
                TypeScript::type(
                    str($validator->name)->afterLast('\\')->toString(),
                    $this->resolveDefinition($validator->rules),
                )->export(),
            );

            foreach ($info as $i) {
                $block->referenceMethod($i[1]->controller(), $i[1]->method(), $i[1]->controllerPath());
            }

            $block->link($validator->name, (new ReflectionClass($validator->name))->getFileName());
        }

        foreach ($this->controllers as $info) {
            $this->addControllerDefinition(
                array_column($info, 1),
                $this->resolveDefinition($info[0][0]->rules),
            );
        }

        foreach ($this->nullValidators as $routes) {
            $this->addControllerDefinition($routes, self::DEFAULT);
        }
    }

    protected function resolveDefinition($rules): string
    {
        $tree = $this->buildTree($this->flattenRules($rules));

        if ($tree['children'] === []) {
            return self::DEFAULT;
        }

        return $this->renderObject($tree['children']);
    }

    /**
     * Rules arrive keyed by Laravel's dotted paths ("meta.keywords.*"), or
     * occasionally already nested, so both are brought to dotted form.
     *
     * @return array<string, Collection>
     */
    protected function flattenRules($rules, string $prefix = ''): array
    {
        $flat = [];

        foreach ($rules as $key => $value) {
            $path = $prefix.$key;

            if ($value instanceof Collection) {
                $value = $value->all();
            }

            if (is_array($value) && ! array_is_list($value)) {
                $flat += $this->flattenRules($value, $path.'.');

                continue;
            }

            $flat[$path] = collect($value);
        }

        return $flat;
    }

    /**
     * @param  array<string, Collection>  $rules
     */
    protected function buildTree(array $rules): array
    {
        $root = ['rules' => null, 'children' => []];

        foreach ($rules as $path => $fieldRules) {
            $node = &$root;

            foreach (explode('.', (string) $path) as $segment) {
                $node['children'][$segment] ??= ['rules' => null, 'children' => []];
                $node = &$node['children'][$segment];
            }

            $node['rules'] = $fieldRules;
            unset($node);
        }

        return $root;
    }

    protected function renderObject(array $children): string
    {
        $fields = collect($children)
            ->reject(fn ($node, $key) => $key === '*')
            ->map(fn ($node, $key) => TypeScript::quoteKey((string) $key)
                .($this->isRequired($node) ? '' : '?')
                .': '.$this->renderNode($node));

        return '{ '.$fields->implode(', ').' }';
    }

    protected function renderNode(array $node): string
    {
        $rules = $node['rules'] ? new Rules($node['rules']) : null;
        $children = $node['children'];

        if ($children === []) {
            return $rules?->resolveFieldType() ?? 'unknown';
        }

        $type = array_keys($children) === ['*']
            ? $this->arrayOf($this->renderNode($children['*']))
            : $this->renderObject($children);

        return $rules?->isNullable() ? $type.' | null' : $type;
    }

    protected function arrayOf(string $type): string
    {
        return str_contains($type, '|') ? "({$type})[]" : "{$type}[]";
    }

    /**
     * A parent without rules of its own must be present whenever one of its
     * named children is required, matching how Laravel validates dotted keys.
     */
    protected function isRequired(array $node): bool
    {
        if ($node['rules']) {
            return (new Rules($node['rules']))->isRequired();
        }

        return collect($node['children'])
            ->reject(fn ($child, $key) => $key === '*')
            ->contains(fn ($child) => $this->isRequired($child));
    }

    protected function routeControllerRequestKey(Route $route): string
    {
        return TypeScript::fqn(
            $route->controller(),
            ucwords($route->method()),
            'Request',
        );
    }

    protected function addControllerDefinition(array $routes, string $definition): void
    {
        $type = TypeScript::type('Request', $definition)->export();

        foreach ($routes as $route) {
            $type->referenceMethod(
                $route->controller(),
                $route->method(),
                $route->controllerPath(),
            );
        }

        TypeScript::addFqnToNamespaced(
            $this->routeControllerRequestKey($routes[0]),
            $type,
        );
    }
}
