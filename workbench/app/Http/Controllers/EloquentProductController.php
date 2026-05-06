<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class EloquentProductController
{
    public function index(): Response
    {
        return Inertia::render('EloquentProducts/Index', [
            'products' => Product::all(),
        ]);
    }

    public function show(Product $product): Response
    {
        return Inertia::render('EloquentProducts/Show', [
            'product' => $product,
        ]);
    }
}
