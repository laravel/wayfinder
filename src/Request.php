<?php

namespace Laravel\Wayfinder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class Request
{
    public Collection $types;

    protected array $optionalRules = [
        'sometimes', 'present_if', 'present_unless', 'present_with', 'present_with_all', 'required_if', 'required_if_accepted', 'required_if_declined', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'missing_if', 'missing_unless', 'missing_with', 'missing_with_all'
    ];

    protected string $nullable = 'nullable';

    protected Collection $booleans;

    protected Collection $strings;

    protected Collection $numbers;

    protected Collection $arrays;

    protected Collection $any;

    // TODO: should manage min, max? handle Rule class

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

            foreach($explodedRules as $rule) {

                if (is_string($rule)) {

                    $escapedRule = Str::take($rule, Str::position($rule, ':'));
                    $escapedRule = $escapedRule === '' ? $rule : $escapedRule;

                    if (in_array($escapedRule, $this->optionalRules)) {
                        $field .= '?';
                    }

                    // if (array_key_exists($rule, $this->rules)) {
                    //     $fieldUnionTypes->push($this->rules[$rule]);
                    // }

                    // booleans
                    if($this->booleans()->has($escapedRule)) {
                        $fieldUnionTypes->push(...$this->booleans()->get($escapedRule));
                    }


                    // strings
                    if($this->strings()->has($escapedRule)) {
                        $fieldUnionTypes->push(...$this->strings()->get($escapedRule));
                    }

                    // numbers
                    if($this->numbers()->has($escapedRule)) {
                        $fieldUnionTypes->push(...$this->numbers()->get($escapedRule));
                    }

                    // arrays
                    if($this->arrays()->has($escapedRule)) {
                        $fieldUnionTypes->push(...$this->arrays()->get($escapedRule));
                    }

                    if($escapedRule === $this->nullable) {
                        $fieldUnionTypes->push('null');
                    }

                    // temporary fix for optional fields
                    if(Str::contains($field, '?') && $fieldUnionTypes->isEmpty()) {
                        $fieldUnionTypes->push('any');
                    }

                } else {
                    // TODO: finish implementation
                //     // var_dump($rule instanceof Rule);

                //     // if($rule instanceof ValidationRule || $rule instanceof Rule) {

                //     $ruleClass = Str::afterLast(get_class($rule), '\\');
                //     // var_dump($ruleClass);
                //     if (in_array($ruleClass, $this->optionalRules)) {
                //         $field .= '?';
                //     }

                //     if (array_key_exists(strtolower($ruleClass), $this->rules)) {
                //         $fieldUnionTypes->push($this->rules[$ruleClass]);
                //     }


                // }
                    
                }

                
            }

            if ($fieldUnionTypes->count() > 0) {
                $rules->push(collect(['field' => $field, 'types' => $fieldUnionTypes->uniqueStrict()->join(' | ')]));
            }
        }
        return $rules;
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
            'uuid' => ['string']
        ]);
    } 

    protected function numbers() {
        return collect([
            'between' => ['number'],
            'decimal' => ['number'],
            'different' => ['number'],
            'digits' => ['number'],
            'digits_between' => ['number'],
            'greater_than' => ['number'],
            'greater_than_or_equal' => ['number'],
            'integer' => ['number'],
            'less_than' => ['number'],
            'less_than_or_equal' => ['number'],
            'max_digits' => ['number'],
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
            'between' => ['number[]', 'string[]'],
            'contains' => ['number[]', 'string[]'],
            'distinct' => ['number[]', 'string[]'],
            'in_array' => ['number[]', 'string[]'],
            'list' => ['number[]', 'string[]'],
            'size' => ['number[]', 'string[]']
        ]);
    } 

    protected function any() {
        return collect([
            'file' => ['any'],
            'fileimage' => ['any'],
            'image' => ['any']
        ]);
    } 

}