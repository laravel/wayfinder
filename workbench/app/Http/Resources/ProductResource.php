<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => 1,
            'name' => 'Widget',
            'price' => 9.99,
            'in_stock' => true,
        ];
    }
}
