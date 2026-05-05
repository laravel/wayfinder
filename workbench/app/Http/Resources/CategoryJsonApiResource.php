<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class CategoryJsonApiResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => 'Tools',
            'slug' => 'tools',
            'created_at' => '2026-01-01',
        ];
    }

    public function toLinks(Request $request): array
    {
        return [
            'self' => '/categories/1',
        ];
    }

    public function toMeta(Request $request): array
    {
        return [
            'count' => 5,
        ];
    }
}
