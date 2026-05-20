<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class PaginatedEloquentProductController
{
    public function paginate(): Response
    {
        return Inertia::render('PaginatedEloquentProducts/Paginate', [
            'products' => Product::paginate(5),
        ]);
    }

    public function simplePaginate(): Response
    {
        return Inertia::render('PaginatedEloquentProducts/SimplePaginate', [
            'products' => Product::simplePaginate(5),
        ]);
    }

    public function cursorPaginate(): Response
    {
        return Inertia::render('PaginatedEloquentProducts/CursorPaginate', [
            'products' => Product::cursorPaginate(5),
        ]);
    }
}
