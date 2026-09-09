<?php

namespace Laravel\Wayfinder\Attributes;

use Attribute;
use Laravel\Surveyor\Contracts\ConditionallyIgnored;

/**
 * Leave the marked declaration out of the generated TypeScript.
 *
 * On a class, nothing is generated for it at all. On a controller method, the
 * route it handles is dropped along with its form variant, page type, and
 * request type. On a property, accessor, relation, enum case, or enum method,
 * that member is dropped from the type around it.
 *
 * Pass `unless` to keep it only while a condition holds, or `when` to leave it
 * out only while one holds. Since generation runs per build, a condition is
 * answered by the environment that generated the files. A condition Wayfinder
 * cannot read leaves the declaration out either way.
 */
#[Attribute(Attribute::TARGET_ALL)]
final class WayfinderIgnore implements ConditionallyIgnored
{
    /**
     * @param  string|array{0: class-string, 1: string}|null  $unless  Keep it while this passes: a config key or a [class, method] callable.
     * @param  string|array{0: class-string, 1: string}|null  $when  Leave it out while this passes.
     */
    public function __construct(
        public readonly string|array|null $unless = null,
        public readonly string|array|null $when = null,
    ) {
        //
    }

    public function unless(): string|array|null
    {
        return $this->unless;
    }

    public function when(): string|array|null
    {
        return $this->when;
    }
}
