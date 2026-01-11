<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller to test deduplication of Inertia components.
 *
 * Multiple methods rendering the same component should only generate
 * a single TypeScript type definition.
 */
class DuplicateInertiaController
{
    public function duplicate(): Response
    {
        return Inertia::render('Dashboard');
    }

    public function duplicateWithData(): Response
    {
        return Inertia::render('Settings/General', [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
    }
}
