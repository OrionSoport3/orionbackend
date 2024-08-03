<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tymon\JWTAuth\Facades\JWTAuth;


class ActivitiesFetched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    

    public array $result;
    public $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($result)
    {
        $this->result = $result;
        $this->userId = JWTAuth::parseToken()->authenticate()->id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('activities-channel.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'ActivitiesFetched';
    }
}
