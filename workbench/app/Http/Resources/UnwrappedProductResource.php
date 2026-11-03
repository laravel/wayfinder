<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnwrappedProductResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'sku' => 'W-1',
            'label' => 'Widget',
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => ['version' => '1'],
        ];
    }
}
