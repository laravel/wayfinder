<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Laravel\Wayfinder\Attributes\WayfinderType;

#[WayfinderType('{ theme: "dark" | "light", notification_enabled: boolean }')]
class SettingsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $decoded = json_decode($value ?? '{}', true);

        return [
            'theme' => $decoded['theme'] ?? 'light',
            'notification_enabled' => $decoded['notification_enabled'] ?? true,
        ];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! is_array($value)) {
            $value = [];
        }

        return json_encode([
            'theme' => isset($value['theme']) && in_array($value['theme'], ['dark', 'light'], true) ? $value['theme'] : 'light',
            'notification_enabled' => isset($value['notification_enabled']) ? (bool) $value['notification_enabled'] : true,
        ]);
    }
}
