<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Wayfinder\Attributes\WayfinderIgnore;

class SecretResource extends JsonResource
{
    #[WayfinderIgnore]
    public function toArray(Request $request): array
    {
        return [
            'socialSecurityNumber' => '000-00-0000',
        ];
    }
}
