<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ModularInertiaController
{
    public function login(): Response
    {
        return Inertia::render('Authorization::Login', [
            'status' => 'ready',
        ]);
    }

    public function register(): Response
    {
        return Inertia::render('Authorization::Register', [
            'terms' => true,
        ]);
    }
}
