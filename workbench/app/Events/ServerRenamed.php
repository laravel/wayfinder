<?php

namespace App\Events;

class ServerRenamed extends BaseServerEvent
{
    public function withData(): array
    {
        return ['name' => 'web-1'];
    }
}
