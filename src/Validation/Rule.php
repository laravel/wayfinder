<?php

namespace Laravel\Wayfinder\Validation;

use Illuminate\Contracts\Validation\CompilableRules;
use Illuminate\Contracts\Validation\Rule as OldValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationRuleParser;

class Rule
{
    protected array $rule;

    public function __construct(
        array|string|OldValidationRule|ValidationRule|CompilableRules $rule,
    ) {
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
