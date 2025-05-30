<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MentionNotification;
use App\Notifications\NewMessageNotification;

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
     * Process mentions in message content.
     */
    private function processMentions(string $content, ?Room $room = null): array
    {
        $mentionedUserIds = [];
        
        // Encontrar todas as menções no formato @username
        preg_match_all('/@(\w+)/', $content, $matches);
        
        if (!empty($matches[1])) {
            $usernames = $matches[1];
            
            // Se estiver em uma sala, verificar apenas membros
            if ($room) {
                $query = $room->is_private 
                    ? $room->members()->whereIn('users.name', $usernames)
                    : User::whereIn('name', $usernames);
            } else {
                $query = User::whereIn('name', $usernames);
            }
            
            // Excluir o próprio usuário das menções
            $query->where('id', '!=', Auth::id());
            
            $mentionedUsers = $query->get();
            $mentionedUserIds = $mentionedUsers->pluck('id')->toArray();
        }
        
        return $mentionedUserIds;
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
        
        $room = null;
        // If it's a room message, check if user is a member
        if ($request->room_id) {
            $room = Room::findOrFail($request->room_id);
            $user = Auth::user();
            $isMember = $user->rooms()->where('room_id', $room->id)->exists();
            
            if (!$isMember && $room->is_private) {
                return back()->with('error', 'You do not have access to this room.');
            }
        }
        
        // Process mentions
        $mentionedUserIds = $this->processMentions($request->content, $room);
        
        $message = Message::create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'room_id' => $request->room_id,
            'receiver_id' => $request->receiver_id,
            'is_read' => false,
            'mentions' => $mentionedUserIds,
        ]);
        
        // Attach mentioned users to the message
        if (!empty($mentionedUserIds)) {
            $message->mentionedUsers()->attach($mentionedUserIds);
            
            // Send notifications to mentioned users
            foreach ($mentionedUserIds as $userId) {
                $mentionedUser = User::find($userId);
                if ($mentionedUser) {
                    $mentionedUser->notify(new MentionNotification($message));
                }
            }
        }
        
        $user = Auth::user();
        
        // Send notifications for new messages
        if ($request->room_id) {
            // Notify all room members except the sender
            $roomMembers = $room->members()->where('users.id', '!=', Auth::id())->get();
            foreach ($roomMembers as $member) {
                $member->notify(new NewMessageNotification($message));
            }
        } else {
            // Notify the receiver of the direct message
            $receiver = User::find($request->receiver_id);
            if ($receiver) {
                $receiver->notify(new NewMessageNotification($message));
            }
        }
        
        // Broadcast the message
        broadcast(new MessageSent($message, $user))->toOthers();
        
        return back();
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
