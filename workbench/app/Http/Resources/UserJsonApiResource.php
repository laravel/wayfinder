<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @mixin User
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
