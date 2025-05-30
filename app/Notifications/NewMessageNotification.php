<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isRoom = $this->message->room_id !== null;
        $shortMessage = substr($this->message->content, 0, 30) . (strlen($this->message->content) > 30 ? '...' : '');
        
        return [
            'message_id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'room_id' => $this->message->room_id,
            'room_name' => $this->message->room ? $this->message->room->name : null,
            'receiver_id' => $this->message->receiver_id,
            'content' => $shortMessage,
            'is_room' => $isRoom,
            'type' => 'new_message',
            'message' => $isRoom 
                ? $this->message->user->name . ' enviou uma mensagem na sala "' . $this->message->room->name . '": "' . $shortMessage . '"'
                : $this->message->user->name . ' enviou uma mensagem: "' . $shortMessage . '"'
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
} 