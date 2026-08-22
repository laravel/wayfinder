<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Wayfinder\Attributes\WayfinderIgnore;

#[WayfinderIgnore]
class IgnoredController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'internalOnly' => 'value',
        ]);
    }
}
