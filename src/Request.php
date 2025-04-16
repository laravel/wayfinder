<?php

namespace Laravel\Wayfinder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class Request
{
    public Collection $types;

    public function __construct(
        public string $className,
        public array $validations
    )
    {
        $this->types = collect([ 'class' => $this->className, 'rules' => $this->resolveTypes($validations)]);
    }

    private function resolveTypes(array $validations): Collection
    {

        $rules = new Collection();

        foreach ($validations as $field => $validationRules) {

            $fieldUnionTypes = new Collection();

            $explodedRules = is_string($validationRules) ? explode('|', $validationRules) : $validationRules;

            $fieldExplicitType = '';

            foreach($explodedRules as $rule) {
                
                $snakeCaseRule = $rule;

                if(! is_string($snakeCaseRule)) {
                    $snakeCaseRule = Str::snake(Str::afterLast(get_class($snakeCaseRule), '\\'));
                }

                if(Str::contains($snakeCaseRule, ':')) {
                    $snakeCaseRule = Str::take($snakeCaseRule, Str::position($snakeCaseRule, ':'));
                }

                if($this->conditionalRules()->contains($snakeCaseRule)) {
                    $field .= Str::contains($field, '?') ? '' : '?';
                }
                
                if($this->explicitTypes()->has($snakeCaseRule)){
                    // $fieldHasExplicitType = true;
                    $fieldExplicitType = $this->explicitTypes()->get($snakeCaseRule);
                }

                if($this->booleans()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->booleans()->get($snakeCaseRule));
                }

                if($this->strings()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->strings()->get($snakeCaseRule));
                }

                if($this->numbers()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->numbers()->get($snakeCaseRule));
                }

                if($this->arrays()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->arrays()->get($snakeCaseRule));
                }

                if($this->files()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->files()->get($snakeCaseRule));
                }

                if($this->nullables()->has($snakeCaseRule)) {
                    $fieldUnionTypes->push(...$this->nullables()->get($snakeCaseRule));
                }

            }

            if ($fieldUnionTypes->count() > 0) {
                $rules->push(collect(['field' => $field, 'types' => $fieldUnionTypes->uniqueStrict()->filter(function(string $type) use ($fieldExplicitType) {
                    if(Str::length($fieldExplicitType) > 0) {

                        if($fieldExplicitType === 'string') {
                            return Str::doesntContain($type, ['[', ']', 'File', 'number']);
                        } else if ($fieldExplicitType === 'number') {
                            return Str::doesntContain($type, ['[', ']', 'File', 'string']);
                        }
                    }
                    return true;
                })->join(' | ')]));
            }
        }
        return $rules;
    }

    protected function explicitTypes() {
        return collect([
            'decimal' => 'number',
            'different' => 'number',
            'digits' => 'number',
            'digits_between' => 'number',
            'gt' => 'number',
            'gte' => 'number',
            'integer' => 'number',
            'lt' => 'number',
            'lte' => 'number',
            'max_digits' => 'number',
            'min_digits' => 'number',
            'multiple_of' => 'number',
            'numeric' => 'number',
            'active_url' => 'string',
            'alpha' => 'string',
            'alpha_dash' => 'string',
            'alpha_numeric' => 'string',
            'ascii' => 'string',
            'confirmed' => 'string',
            'current_password' => 'string',
            'different' => 'string',
            'doesnt_start_with' => 'string',
            'doesnt_end_with' => 'string',
            'email' => 'string',
            'ends_with' => 'string',
            'enum' => 'string',
            'hex_color' => 'string',
            'in' => 'string',
            'ip_address' => 'string',
            'json' => 'string',
            'lowercase' => 'string',
            'mac_address' => 'string',
            'not_in' => 'string',
            'regular_expression' => 'string',
            'not_regular_expression' => 'string',
            'same' => 'string',
            'starts_with' => 'string',
            'string' => 'string',
            'uppercase' => 'string',
            'url' => 'string',
            'ulid' => 'string',
            'uuid' => 'string',
        ]);
    }

    protected function booleans() {
        return collect([
            'accepted' => ["'yes'", "'on'", 1, "'1'", "'true'", 'true'],
            'accepted_if' => ["'yes'", "'on'", 1, "'1'", "'true'", 'true'],
            'boolean' => ['boolean', 1, 0, "'1'", "'0'"],
            'declined' => ["'no'", "'off'", 0, "'0'", "'false'", 'false'],
            'declined_if' => ["'no'", "'off'", 0, "'0'", "'false'", 'false'],
        ]);  
    } 

    protected function strings() {
        return collect([
            'active_url' => ['string'],
            'alpha' => ['string'],
            'alpha_dash' => ['string'],
            'alpha_numeric' => ['string'],
            'ascii' => ['string'],
            'confirmed' => ['string'],
            'current_password' => ['string'],
            'different' => ['string'],
            'doesnt_start_with' => ['string'],
            'doesnt_end_with' => ['string'],
            'email' => ['string'],
            'ends_with' => ['string'],
            'enum' => ['string'],
            'hex_color' => ['string'],
            'in' => ['string'],
            'ip_address' => ['string'],
            'json' => ['string'],
            'lowercase' => ['string'],
            'mac_address' => ['string'],
            'max' => ['string'],
            'min' => ['string'],
            'not_in' => ['string'],
            'regular_expression' => ['string'],
            'not_regular_expression' => ['string'],
            'same' => ['string'],
            'size' => ['string'],
            'starts_with' => ['string'],
            'string' => ['string'],
            'uppercase' => ['string'],
            'url' => ['string'],
            'ulid' => ['string'],
            'uuid' => ['string'],
        ]);
    } 

    protected function numbers() {
        return collect([
            'between' => ['number'],
            'decimal' => ['number'],
            'different' => ['number'],
            'digits' => ['number'],
            'digits_between' => ['number'],
            'gt' => ['number'],
            'gte' => ['number'],
            'integer' => ['number'],
            'lt' => ['number'],
            'lte' => ['number'],
            'max' => ['number'],
            'max_digits' => ['number'],
            'min' => ['number'],
            'min_digits' => ['number'],
            'multiple_of' => ['number'],
            'numeric' => ['number'],
            'same' => ['number'],
            'size' => ['number']
        ]);
    } 

    protected function arrays() {
        return collect([
            'array' => ['number[]', 'string[]'],
            'array_rule' => ['number[]', 'string[]'],
            'between' => ['number[]', 'string[]'],
            'contains' => ['number[]', 'string[]'],
            'distinct' => ['number[]', 'string[]'],
            'in_array' => ['number[]', 'string[]'],
            'max' => ['number[]', 'string[]'],
            'min' => ['number[]', 'string[]'],
            'list' => ['number[]', 'string[]'],
            'size' => ['number[]', 'string[]'],
        ]);
    } 

    /**
     * The between validation rule is disabled here to avoid conflicts with the same rule for numbers and strings. Same reasoning was made for the size rule.
     */
    protected function files() {
        return collect([
            'between' => ['File'],
            'dimensions' => ['File'],
            'extensions' => ['File'],
            'file' => ['File'],
            'image' => ['File'],
            'image_file' => ['File'],
            'max' => ['File'],
            'mimes' => ['File'],
            'size' => ['File'],
        ]);
    }

    protected function nullables() {
        return collect([
            'nullable' => ['null']
        ]);
    }

    protected function conditionalRules() {
        return collect([
            'sometimes',
            'present_if',
            'present_unless',
            'present_with',
            'present_with_all',
            'required_if',
            'required_if_accepted',
            'required_if_declined',
            'required_unless',
            'required_with',
            'required_with_all',
            'required_without',
            'required_without_all',
            'missing_if',
            'missing_unless',
            'missing_with',
            'missing_with_all',
            'conditional_rules'
        ]);
    }

}