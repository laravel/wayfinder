<?php

namespace TiMacDonald\Wayfinder;

class BindingResolver
{
    protected static $models = [];

    protected static $columns = [];

    public static function resolveTypeAndKey(string $model, $key): array
    {
        $booted = self::$models[$model] ??= app($model);

        $key ??= $booted->getRouteKeyName();

        self::$columns[$model] ??= $booted->getConnection()->getSchemaBuilder()->getColumns($booted->getTable());

        return [
            collect(self::$columns[$model])->first(
                fn ($column) => $column['name'] === $key,
            )['type_name'] ?? null,
            $key,
        ];
    }
}
