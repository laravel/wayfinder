<?php

use Laravel\Wayfinder\Langs\TypeScript\Imports;

it('preserves type-only namespace imports', function () {
    $imports = Imports::create()->addWildcard('./types', 'QueryTypes', type: true);

    expect((string) $imports)->toBe('import type * as QueryTypes from "./types";');
});
