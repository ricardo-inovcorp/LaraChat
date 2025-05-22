<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all direct messages involving the current user
        $user = Auth::user();
        $directMessages = Message::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereNotNull('receiver_id');
        })->orWhere(function($query) use ($user) {
            $query->where('receiver_id', $user->id);
        })->with(['user', 'receiver'])->orderBy('created_at', 'desc')->get()
          ->groupBy(function($message) use ($user) {
              return $message->user_id == $user->id ? $message->receiver_id : $message->user_id;
          });
        
        // Get users who have direct messages with the current user
        $userIds = $directMessages->keys();
        $users = User::whereIn('id', $userIds)->get();
        
        return view('messages.index', compact('directMessages', 'users'));
    }

    /**
     * Show direct message conversation with a user.
     */
    public function conversation(User $user)
    {
        $currentUser = Auth::user();
        
        // Get all messages between the two users
        $messages = Message::where(function($query) use ($currentUser, $user) {
            $query->where('user_id', $currentUser->id)
                  ->where('receiver_id', $user->id);
        })->orWhere(function($query) use ($currentUser, $user) {
            $query->where('user_id', $user->id)
                  ->where('receiver_id', $currentUser->id);
        })->with('user')->orderBy('created_at', 'asc')->get();
        
        // Mark all unread messages as read
        Message::where('user_id', $user->id)
              ->where('receiver_id', $currentUser->id)
              ->where('is_read', false)
              ->update(['is_read' => true]);
        
        return view('messages.conversation', compact('messages', 'user'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'room_id' => 'nullable|exists:rooms,id',
            'receiver_id' => 'nullable|exists:users,id',
        ]);
        
        // Either room_id or receiver_id must be provided, but not both
        if (($request->room_id && $request->receiver_id) || (!$request->room_id && !$request->receiver_id)) {
            return back()->with('error', 'Invalid message destination');
        }
        
        // If it's a room message, check if user is a member
        if ($request->room_id) {
            $room = Room::findOrFail($request->room_id);
            $user = Auth::user();
            $isMember = $user->rooms()->where('room_id', $room->id)->exists();
            
            if (!$isMember && $room->is_private) {
                return back()->with('error', 'You do not have access to this room.');
            }
        }
        
        $message = Message::create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'room_id' => $request->room_id,
            'receiver_id' => $request->receiver_id,
            'is_read' => false,
        ]);
        
        $user = Auth::user();
        
        try {
            // Configurar o Pusher
            $options = [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true
            ];
            
            $pusher = new \Pusher\Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            
            $data = [
                'message' => $message->content,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'created_at' => $message->created_at,
                'message_id' => $message->id
            ];
            
            if ($request->room_id) {
                // Canal para mensagens de sala
                $channel = 'chat-room.' . $request->room_id;
                $event = 'my-event';
                
                Log::debug('Enviando mensagem de sala via Pusher', [
                    'channel' => $channel,
                    'event' => $event,
                    'data' => $data
                ]);
                
                // Trigger para mensagens de sala
                $pusher->trigger($channel, $event, $data);
            } else {
                // Canal para mensagens privadas
                $receiverId = $request->receiver_id;
                $senderId = $user->id;
                
                // Canal para quando este usuário é o remetente
                $senderChannel = 'chat.' . $senderId . '.' . $receiverId;
                // Canal para quando este usuário é o destinatário
                $receiverChannel = 'chat.' . $receiverId . '.' . $senderId;
                
                $event = 'new-message';
                
                Log::debug('Enviando mensagem privada via Pusher', [
                    'senderChannel' => $senderChannel,
                    'receiverChannel' => $receiverChannel,
                    'event' => $event,
                    'data' => $data
                ]);
                
                // Disparar evento em ambos os canais
                $pusher->trigger([$senderChannel, $receiverChannel], $event, $data);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem via Pusher', [
                'error' => $e->getMessage(),
            ]);
        }
        
        if ($request->room_id) {
            return redirect()->route('rooms.show', $request->room_id);
        } else {
            return redirect()->route('messages.conversation', $request->receiver_id);
        }
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Message $message)
    {
        // Check if the current user is the receiver
        if ($message->receiver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $message->update(['is_read' => true]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        // Check if the user owns the message
        if ($message->user_id !== Auth::id()) {
            return back()->with('error', 'You do not have permission to delete this message.');
        }
        
        $message->delete();
        
        return back()->with('success', 'Message deleted successfully.');
    }
}
