<?php

namespace Laravel\Wayfinder\Registry;

use Laravel\Surveyor\Types\Contracts\Type;

interface ConverterInterface
{
    public function convert(Type $result): string;
}
