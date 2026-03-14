<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * This controller triggers a bug in Wayfinder where Inertia component paths
 * create a collision in undot(). The paths:
 *   - "Items" -> Inertia.Pages.Items
 *   - "Items/Edit" -> Inertia.Pages.Items.Edit
 *
 * When undotted, this creates a mixed array where "Items" has both a
 * VariableBuilder (from the index route) and a child key "Edit".
 */
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
