<?php

namespace Laravel\Wayfinder\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WayfinderPropertyType
{
    public function __construct(
        public readonly string $property,
        public readonly string $type,
    ) {
        //
    }
}
