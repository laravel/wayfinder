<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\URL;

class UrlDefaultsMiddleware
{
    public function handle($request, $next)
    {
        URL::defaults([
            'locale' => 'en',
            'quoted' => 'say "hi" now',
        ]);

        return $next($request);
    }
}
