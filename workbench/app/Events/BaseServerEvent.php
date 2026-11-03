<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

abstract class BaseServerEvent implements ShouldBroadcast
{
    public function __construct(public int $serverId)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [new Channel('servers.'.$this->serverId)];
    }

    public function broadcastWith(): array
    {
        return [
            'event' => static::class,
            'data' => $this->withData(),
        ];
    }

    public function withData(): array
    {
        return [];
    }
}
