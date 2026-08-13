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
        $tree = $this->buildDefinitionTree($rules);

        if ($tree === []) {
            return self::DEFAULT;
        }

        $internalDefinition = collect($tree)
            ->map(fn ($node, $key) => $this->toDefinition($node, $key, 1))
            ->filter()
            ->implode(PHP_EOL);

        if ($internalDefinition === '') {
            return self::DEFAULT;
        }

        return '{'.$internalDefinition.PHP_EOL.'}';
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

    protected function toDefinition($node, $key, $indent = 1): string
    {
        $key = TypeScript::quoteKey($key);
        $def = TypeScript::indent($key, $indent);

        $isRequired = $this->nodeIsRequired($node);
        $def .= $isRequired ? ': ' : '?: ';

        if ($this->isLeafNode($node)) {
            $def .= $this->nodeLeafType($node).';';

            return $def;
        }

        if ($this->nodeIsWildcardArray($node)) {
            $itemNode = $node['children']['*'];

            if ($this->isLeafNode($itemNode)) {
                $def .= $this->nodeLeafType($itemNode).'[];';

                return $def;
            }

            $children = collect($itemNode['children'] ?? [])
                ->map(fn ($child, $childKey) => $this->toDefinition($child, $childKey, $indent + 1))
                ->filter()
                ->implode(PHP_EOL);

            $def .= '{'.PHP_EOL.$children.PHP_EOL.TypeScript::indent('}[];', $indent);

            return $def;
        }

        $children = collect($node['children'] ?? [])
            ->map(fn ($child, $childKey) => $this->toDefinition($child, $childKey, $indent + 1))
            ->filter()
            ->implode(PHP_EOL);

        if ($children === '') {
            $def .= self::DEFAULT.';';

            return $def;
        }

        $def .= '{'.PHP_EOL.$children.PHP_EOL.TypeScript::indent('};', $indent);

        return $def;
    }

    protected function buildDefinitionTree($rules): array
    {
        $tree = [];

        foreach ($rules as $key => $ruleSet) {
            $segments = explode('.', (string) $key);
            $this->insertRuleIntoTree($tree, $segments, $ruleSet);
        }

        return $tree;
    }

    protected function insertRuleIntoTree(array &$tree, array $segments, $ruleSet): void
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return;
        }

        $tree[$segment] ??= [
            'rules' => null,
            'children' => [],
        ];

        if ($segments === []) {
            $tree[$segment]['rules'] = $ruleSet instanceof Collection ? $ruleSet : collect($ruleSet);

            return;
        }

        $this->insertRuleIntoTree($tree[$segment]['children'], $segments, $ruleSet);
    }

    protected function isLeafNode(array $node): bool
    {
        return ! empty($node['rules']) && empty($node['children']);
    }

    protected function nodeLeafType(array $node): string
    {
        return (new Rules($node['rules']))->resolveFieldType();
    }

    protected function nodeIsRequired(array $node): bool
    {
        if (! empty($node['rules']) && (new Rules($node['rules']))->isRequired()) {
            return true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if ($this->nodeIsRequired($child)) {
                return true;
            }
        }

        return false;
    }

    protected function nodeIsWildcardArray(array $node): bool
    {
        return array_key_exists('*', $node['children'] ?? []);
    }
}
