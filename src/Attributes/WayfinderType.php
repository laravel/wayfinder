<?php

namespace Laravel\Wayfinder\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class WayfinderType
{
    public function __construct(public readonly string $type)
    {
        //
    }
}
