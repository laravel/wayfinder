<?php

namespace Laravel\Wayfinder\Validation;

use Illuminate\Contracts\Validation\CompilableRules;
use Illuminate\Contracts\Validation\Rule as OldValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationRuleParser;
use Laravel\Ranger\Validation\Rule as RangerRule;

class Rule
{
    protected array $rule;

    public function __construct(
        array|string|OldValidationRule|ValidationRule|CompilableRules|RangerRule $rule,
    ) {
        if ($rule instanceof RangerRule) {
            $this->rule = [$rule->rule(), $rule->getParams()];

            return;
        }

        if (is_array($rule) && ($rule[0] ?? null) instanceof RangerRule) {
            /** @var RangerRule $first */
            $first = $rule[0];
            $this->rule = [$first->rule(), $first->getParams()];

            return;
        }

        $this->rule = ValidationRuleParser::parse($rule);
    }

    public function is(string $id): bool
    {
        return $this->rule[0] === $id;
    }

    public function isEnum(): bool
    {
        return $this->rule[0] instanceof Enum;
    }

    public function rule()
    {
        return $this->rule[0];
    }

    public function getParams(): array
    {
        return $this->rule[1];
    }

    public function hasParams(): bool
    {
        return count($this->getParams()) > 1;
    }
}
