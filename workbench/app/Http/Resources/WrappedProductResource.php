<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WrappedProductResource extends JsonResource
{
    public static $wrap = 'product';

    public function toArray(Request $request): array
    {
        return [
            'id' => 1,
            'name' => 'Widget',
        ];
    }
}
