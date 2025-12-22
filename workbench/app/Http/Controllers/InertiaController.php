<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class InertiaController
{
    public function dashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'users' => 100,
                'posts' => 50,
                'views' => 10000,
            ],
            'recentActivity' => [],
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('Settings/General', [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'preferences' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
        ]);
    }

    public function profile(): Response
    {
        return Inertia::render('Profile/Show', [
            'profile' => [
                'bio' => 'Hello world',
                'avatar' => null,
            ],
        ]);
    }
}
