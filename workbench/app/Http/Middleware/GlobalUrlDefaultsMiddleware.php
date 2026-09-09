<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\URL;

class GlobalUrlDefaultsMiddleware
{
    public function handle($request, $next)
    {
        URL::defaults([
            'locale' => 'global',
        ]);

        return $next($request);
    }
}
