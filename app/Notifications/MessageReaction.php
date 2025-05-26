<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageReaction extends Notification
{
    use Queueable;

    protected $message;
    protected $reactor;
    protected $emoji;
    protected $isRoom;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message, User $reactor, string $emoji)
    {
        $this->message = $message;
        $this->reactor = $reactor;
        $this->emoji = $emoji;
        $this->isRoom = $message->room_id !== null;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $context = $this->isRoom ? 'em uma sala' : 'em uma conversa';
        
        return (new MailMessage)
            ->subject($this->reactor->name . ' reagiu à sua mensagem')
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line($this->reactor->name . ' reagiu com ' . $this->emoji . ' à sua mensagem ' . $context . '.')
            ->action(
                'Ver mensagem', 
                $this->isRoom 
                    ? url('/rooms/' . $this->message->room_id) 
                    : url('/messages/conversation/' . $this->reactor->id)
            )
            ->line('Obrigado por usar nossa aplicação!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $context = $this->isRoom ? 'em uma sala' : 'em uma conversa';
        $shortMessage = substr($this->message->content, 0, 30) . (strlen($this->message->content) > 30 ? '...' : '');
        
        return [
            'message_id' => $this->message->id,
            'reactor_id' => $this->reactor->id,
            'reactor_name' => $this->reactor->name,
            'emoji' => $this->emoji,
            'is_room' => $this->isRoom,
            'room_id' => $this->message->room_id,
            'conversation_user_id' => $this->isRoom ? null : $this->reactor->id,
            'message_preview' => $shortMessage,
            'message' => $this->reactor->name . ' reagiu com ' . $this->emoji . ' à sua mensagem "' . $shortMessage . '" ' . $context . '.'
        ];
    }
} 