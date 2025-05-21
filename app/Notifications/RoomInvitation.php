<?php

namespace App\Notifications;

use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoomInvitation extends Notification
{
    use Queueable;

    protected $room;
    protected $inviter;

    /**
     * Create a new notification instance.
     */
    public function __construct(Room $room, User $inviter)
    {
        $this->room = $room;
        $this->inviter = $inviter;
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
        return (new MailMessage)
            ->subject('Você foi convidado para uma sala')
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line('Você foi convidado por ' . $this->inviter->name . ' para participar da sala "' . $this->room->name . '".')
            ->action('Acessar Sala', url('/rooms/' . $this->room->id))
            ->line('Obrigado por usar nossa aplicação!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'message' => 'Você foi convidado por ' . $this->inviter->name . ' para participar da sala "' . $this->room->name . '".'
        ];
    }
}
