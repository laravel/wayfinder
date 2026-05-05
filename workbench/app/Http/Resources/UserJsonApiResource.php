<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin \App\Models\User
 */
class UserJsonApiResource extends JsonApiResource
{
    public static $relationships = ['featuredProduct', 'relatedProducts'];

    public function toAttributes(Request $request): array
    {
        return [
            'name' => 'Joe',
            'email' => 'joe@example.com',
        ];
    }
}
