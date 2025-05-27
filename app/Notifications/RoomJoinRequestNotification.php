<?php

namespace App\Notifications;

use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoomJoinRequestNotification extends Notification
{
    use Queueable;

    protected $room;
    protected $requester;

    public function __construct(Room $room, User $requester)
    {
        $this->room = $room;
        $this->requester = $requester;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'message' => $this->requester->name . ' solicitou acesso à sala "' . $this->room->name . '".'
        ];
    }
} 