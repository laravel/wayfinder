<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MixedRouteController
{
    public function index(): Response
    {
        return Inertia::render('Items', [
            'items' => [],
        ]);
    }

    public function edit(int $item): Response
    {
        return Inertia::render('Items/Edit', [
            'item' => ['id' => $item],
        ]);
    }

    public function update(int $item): Response
    {
        return Inertia::render('Items/Edit', [
            'item' => ['id' => $item],
        ]);
    }
}
