<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, User $user)
    {
        $this->message = $message;
        $this->user = $user;
        
        Log::debug('MessageSent criado', [
            'message_id' => $message->id,
            'content' => $message->content,
            'user_id' => $user->id,
            'username' => $user->name,
            'room_id' => $message->room_id
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Simplificar para canal normal
        $channelName = 'chat-room.' . $this->message->room_id;
        Log::debug('Broadcasting para canal', ['channel' => $channelName]);
        
        return [new Channel($channelName)];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'my-event';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message->content,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->message->created_at,
            'message_id' => $this->message->id,
            'mentions' => $this->message->mentions,
            'mentioned_users' => $this->message->mentionedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            }),
        ];
    }
}
