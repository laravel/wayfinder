<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ServerProvisioned implements ShouldBroadcast
{
    public function __construct(public int $serverId, public string $ip)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [new Channel('servers.'.$this->serverId)];
    }

    public function broadcastAs(): string
    {
        return 'server.provisioned';
    }
}
