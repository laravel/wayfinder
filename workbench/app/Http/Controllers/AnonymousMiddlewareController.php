<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class AnonymousMiddlewareController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }

    public function show()
    {
        //
    }
}
