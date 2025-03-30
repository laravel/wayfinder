<?php

namespace Laravel\Wayfinder;

class Verb
{
    public string $formSafe;

    public string $property;

    public function __construct(public string $actual)
    {
        $this->actual = strtolower($actual);

        $this->formSafe = in_array(strtolower($actual), ['get', 'head', 'options'], true) ? 'get' : 'post';
    }
}
