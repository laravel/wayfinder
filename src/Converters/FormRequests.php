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

        $rules = [];

        foreach ($this->controllers as $info) {
            foreach ($info[0][0]->rules as $key => $value) {
                $this->setNestedRule($rules, $key, $value);
            }

            $this->addControllerDefinition(
                array_column($info, 1),
                $this->resolveDefinition($rules),
            );
        }

        foreach ($this->nullValidators as $routes) {
            $this->addControllerDefinition($routes, self::DEFAULT);
        }
    }

    protected function resolveDefinition($rules): string
    {
        $internalDefinition = collect($rules)
            ->map($this->toDefinition(...))
            ->values()
            ->implode(PHP_EOL);

        if ($internalDefinition === '') {
            return self::DEFAULT;
        }

        return '{'.$internalDefinition.'}';
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

    protected function toDefinition($rules, $key, $indent = 1)
    {
        $key = TypeScript::quoteKey($key);
        $def = TypeScript::indent($key, $indent);

        // Leaf rules can arrive as a single rule object/string instead of a list.
        // Normalize them so they are handled by the list-rule branch below.
        if (! ($rules instanceof Collection) && ! is_array($rules)) {
            $rules = [$rules];
        }

        if (($rules instanceof Collection && array_is_list($rules->all())) || (is_array($rules) && array_is_list($rules))) {
            $collection = $rules instanceof Collection ? $rules : collect($rules);
            $rulesHelper = new Rules($collection);

            if ($rulesHelper->isRequired()) {
                $def .= ': ';
            } else {
                $def .= '?: ';
            }

            $def .= $rulesHelper->resolveFieldType().';';

            return $def;
        }

        $rulesArray = $rules instanceof Collection ? $rules->all() : $rules;
        $wildcard = in_array('*', array_keys($rulesArray));

        if ($wildcard) {
            $wildcardRules = $rulesArray['*'] ?? [];
            $wildcardRules = $wildcardRules instanceof Collection ? $wildcardRules->all() : $wildcardRules;
            $wildcardRules = is_array($wildcardRules) ? $wildcardRules : [$wildcardRules];

            $containerRules = collect($rulesArray)
                ->except('*')
                ->filter(fn ($_value, $subKey) => is_int($subKey))
                ->values();

            if (array_is_list($wildcardRules) && ($containerRules->isEmpty() || array_is_list($containerRules->all()))) {
                $itemRules = new Rules(collect($wildcardRules));
                $containerRulesHelper = new Rules($containerRules);

                if ($containerRulesHelper->isRequired() || $itemRules->isRequired()) {
                    $def .= ': ';
                } else {
                    $def .= '?: ';
                }

                $itemType = $itemRules->resolveFieldType().'[]';

                if ($containerRulesHelper->isNullable()) {
                    $itemType .= ' | null';
                }

                return $def.$itemType.';';
            }
        }

        $subDef = '';

        foreach ($rulesArray as $subKey => $subRules) {
            if ($subKey === '*') {
                foreach ($subRules as $grandKey => $subRule) {
                    $subDef .= PHP_EOL.$this->toDefinition($subRule, $grandKey, $indent + 1);
                }
            } else {
                $subDef .= PHP_EOL.$this->toDefinition($subRules, $subKey, $indent + 1);
            }
        }

        // Match any colon without a preceding question mark
        if (preg_match('/(?<!\?)\:/', $subDef) || $wildcard) {
            // If anything below is required,
            // we need to ensure the parent is also required
            $def .= ': {';
        } else {
            $def .= '?: {';
        }

        $def .= $subDef;

        if ($wildcard) {
            $def .= PHP_EOL.TypeScript::indent('}[]');
        } else {
            $def .= PHP_EOL.TypeScript::indent('}');
        }

        return $def;
    }

    protected function setNestedRule(array &$rules, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$rules;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $current[$segment] = $value;

                return;
            }

            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }
}
