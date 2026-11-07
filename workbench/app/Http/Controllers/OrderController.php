<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchOrdersRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController
{
    public function index(SearchOrdersRequest $request)
    {
        return Inertia::render('Orders/Index', []);
    }

    public function show(SearchOrdersRequest $request, string $order)
    {
        return [];
    }

    public function store(SearchOrdersRequest $request)
    {
        return [];
    }

    public function search(SearchOrdersRequest $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        $request->validate([
            'filters.archived' => ['sometimes', 'boolean'],
        ]);

        return [];
    }

    public function required(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:pending,paid,shipped'],
            'page' => ['sometimes', 'integer'],
        ]);

        return [];
    }
}
