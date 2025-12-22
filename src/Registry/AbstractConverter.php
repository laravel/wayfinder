<?php

namespace Laravel\Wayfinder\Registry;

use Laravel\Surveyor\Types\Contracts\Type;

abstract class AbstractConverter implements ConverterInterface
{
    abstract public function convert(Type $result): string;
}
