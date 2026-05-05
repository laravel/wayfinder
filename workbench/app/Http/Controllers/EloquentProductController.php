<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Product;
use Inertia\Inertia;

class EloquentProductController
{
    public function index()
    {
        return Inertia::render('eloquent_products/index', [
            'products' => Product::all(),
        ]);
    }

    public function create()
    {
        //
    }

    public function store(StorePostRequest $request)
    {
        //
    }

    public function show(Product $product)
    {
        return Inertia::render('eloquent_products/show', [
            'product' => $product,
        ]);
    }

    public function edit()
    {
        //
    }

    public function update()
    {
        //
    }

    public function destroy()
    {
        //
    }
}
