<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Mark a notification as read and redirect to the appropriate page.
     */
    public function markAsRead(Request $request, $id)
    {
        // Encontrar a notificação pelo ID
        $notification = DatabaseNotification::findOrFail($id);
        
        // Verificar se a notificação pertence ao usuário logado
        if ($notification->notifiable_id != Auth::id() || $notification->notifiable_type != get_class(Auth::user())) {
            abort(403, 'Acesso não autorizado a esta notificação');
        }
        
        // Marcar a notificação como lida
        $notification->markAsRead();
        
        // Log para depuração
        Log::info('Notificação marcada como lida', [
            'notification_id' => $id,
            'user_id' => Auth::id(),
            'data' => $notification->data
        ]);
        
        // Redirect to the appropriate page based on notification type
        if (isset($notification->data['message_id'])) {
            // Se é uma notificação de reação a mensagem
            if (isset($notification->data['is_room']) && $notification->data['is_room']) {
                // Se a mensagem está em uma sala
                return redirect()->route('rooms.show', $notification->data['room_id']);
            } else {
                // Se a mensagem é de uma conversa direta
                return redirect()->route('messages.conversation', $notification->data['conversation_user_id']);
            }
        } elseif (isset($notification->data['room_id'])) {
            // Se é uma notificação de convite para sala
            return redirect()->route('rooms.show', $notification->data['room_id']);
        }
        
        return redirect()->back();
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return redirect()->back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }
}
