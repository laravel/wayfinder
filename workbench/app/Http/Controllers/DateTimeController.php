<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DateTimeController
{
    public function show(): Response
    {
        return Inertia::render('DateTime/Show', [
            'immutable' => new DateTimeImmutable,
            'mutable' => new DateTime,
        ]);
    }
}
