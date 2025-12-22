<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ApiController
{
    /**
     * @return JsonResponse<array{status: string, version: string, uptime: int}>
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'version' => '1.0.0',
            'uptime' => 12345,
        ]);
    }

    /**
     * @return JsonResponse<array{users: array<int, array{id: int, name: string}>, total: int}>
     */
    public function users(): JsonResponse
    {
        return response()->json([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ],
            'total' => 2,
        ]);
    }
}
