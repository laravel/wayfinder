<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class ClassReferenceController
{
    public function show(): Response
    {
        return Inertia::render('ClassReference/Show', [
            'price' => new Money(500),
            'owner' => User::firstOrFail(),
            'ownerClass' => User::class,
            'session' => Inertia::once(fn () => ['id' => 1, 'name' => 'Taylor'])->fresh(false),
            'feed' => Inertia::defer(fn () => ['cursor' => 'abc'])->merge(),
        ]);
    }
}
