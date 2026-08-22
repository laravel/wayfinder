<?php

namespace App\Http\Controllers;

use App\Http\Resources\SecretResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Wayfinder\Attributes\WayfinderIgnore;

class SecretsController
{
    public function index(): Response
    {
        return Inertia::render('Secrets', [
            'name' => 'Taylor',
            'socialSecurityNumber' => '000-00-0000', // @wayfinder-ignore
            'account' => [
                'label' => 'Primary',
                // @wayfinder-ignore
                'routingNumber' => '000000000',
            ],
            'email' => 'taylor@laravel.com',
        ]);
    }

    public function resource(): JsonResource
    {
        return new SecretResource(null);
    }

    #[WayfinderIgnore]
    public function reveal(): Response
    {
        return Inertia::render('Reveal', [
            'internalOnly' => 'value',
        ]);
    }
}
