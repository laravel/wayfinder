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

    public function unsafe(): Response
    {
        return Inertia::render('settings/two-factor', [
            'user' => [
                'name' => 'Jane Doe',
                'email' => 'jane@doe.co',
            ],
        ]);
    }

    public function conditional(): Response
    {
        if (auth()->check()) {
            return Inertia::render('Conditional/Authenticated', [
                'user' => auth()->user(),
            ]);
        }

        return Inertia::render('Conditional/Guest', [
            'canLogin' => true,
        ]);
    }

    public function inlineAssignment(): Response
    {
        return inertia('InlineAssignment', [
            'stats' => $stats = ['a' => 1, 'b' => 2],
            'first' => $stats['a'],
        ]);
    }
}
