<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\UserRoom;
use App\Models\RoomJoinRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
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
        // Get all public rooms with their creators
        $publicRooms = Room::where('is_private', false)->with('creator')->get();
        
        // Get user's private rooms with their creators
        $user = Auth::user();
        $userRooms = $user->rooms()->where('is_private', true)->with('creator')->get();
        
        // Get rooms the user is a member of
        $userRoomIds = $user->rooms()->pluck('rooms.id')->toArray();
        
        // Get user's pending join requests
        $pendingRequests = $user->joinRequests()->where('status', 'pending')->pluck('room_id')->toArray();
        
        return view('rooms.index', compact('publicRooms', 'userRooms', 'userRoomIds', 'pendingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('rooms.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20',
            'description' => 'nullable|string|max:30',
            'is_private' => 'boolean',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id'
        ]);
        
        $user = Auth::user();
        $isPrivate = $request->is_private ?? false;
        
        // Only administrators can create public rooms
        if (!$user->isAdmin() && !$isPrivate) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Apenas administradores podem criar salas públicas. Por favor, crie uma sala privada.');
        }
        
        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_private' => $isPrivate
        ]);
        
        // Add the creator as an admin member
        UserRoom::create([
            'user_id' => Auth::id(),
            'room_id' => $room->id,
            'is_admin' => true
        ]);
        
        // Add other members if provided
        if ($request->has('members')) {
            foreach ($request->members as $memberId) {
                UserRoom::create([
                    'user_id' => $memberId,
                    'room_id' => $room->id,
                    'is_admin' => false
                ]);
                
                // Send notification to the invited user
                $invitedUser = User::find($memberId);
                $invitedUser->notify(new \App\Notifications\RoomInvitation($room, $user));
            }
        }
        
        return redirect()->route('rooms.show', $room)->with('success', 'Room created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room, Request $request)
    {
        // Check if user is member of the room
        $user = Auth::user();
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        
        // For non-admin users, prevent access if not a member (both private and public rooms)
        if (!$isMember && !$user->isAdmin()) {
            return redirect()->route('rooms.index')->with('error', 'Você não tem acesso a esta sala. Solicite acesso ao proprietário.');
        }
        
        // Mark notification as read if notification_id is provided
        if ($request->has('notification_id')) {
            $notification = $user->notifications()->find($request->notification_id);
            if ($notification) {
                $notification->markAsRead();
            }
        }
        
        // Mark all room invitation notifications for this room as read
        foreach ($user->unreadNotifications as $notification) {
            if (isset($notification->data['room_id']) && $notification->data['room_id'] == $room->id) {
                $notification->markAsRead();
            }
        }
        
        $messages = $room->messages()->with('user')->orderBy('created_at', 'asc')->get();
        $members = $room->members()->get();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        return view('rooms.show', compact('room', 'messages', 'members', 'isAdmin'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        // Check if user is admin of the room
        $user = Auth::user();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        if (!$isAdmin) {
            return redirect()->route('rooms.show', $room)->with('error', 'You do not have permission to edit this room.');
        }
        
        $users = User::where('id', '!=', Auth::id())->get();
        $members = $room->members()->where('users.id', '!=', Auth::id())->pluck('users.id')->toArray();
        
        return view('rooms.edit', compact('room', 'users', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        // Check if user is admin of the room
        $user = Auth::user();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        if (!$isAdmin) {
            return redirect()->route('rooms.show', $room)->with('error', 'You do not have permission to update this room.');
        }
        
        $request->validate([
            'name' => 'required|string|max:20',
            'description' => 'nullable|string|max:30',
            'is_private' => 'boolean',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id'
        ]);
        
        $room->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_private' => $request->is_private ?? false
        ]);
        
        // Update members
        if ($request->has('members')) {
            // Get current members before removing them
            $currentMembers = $room->members()->where('users.id', '!=', Auth::id())->pluck('users.id')->toArray();
            
            // Remove all non-admin members
            UserRoom::where('room_id', $room->id)
                    ->where('user_id', '!=', Auth::id())
                    ->delete();
            
            // Add new members
            foreach ($request->members as $memberId) {
                UserRoom::updateOrCreate(
                    ['user_id' => $memberId, 'room_id' => $room->id],
                    ['is_admin' => false]
                );
                
                // Send notification to newly added members
                if (!in_array($memberId, $currentMembers)) {
                    $invitedUser = User::find($memberId);
                    $invitedUser->notify(new \App\Notifications\RoomInvitation($room, $user));
                }
            }
        }
        
        return redirect()->route('rooms.show', $room)->with('success', 'Room updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        $user = Auth::user();
        
        // Allow admin users to delete any room
        if ($user->isAdmin() || $room->created_by === $user->id) {
            // Delete related records to avoid foreign key constraint violations
            // Delete all messages in the room
            $room->messages()->delete();
            
            // Delete all user-room relationships
            $room->members()->detach();
            
            // Delete all join requests for the room
            $room->joinRequests()->delete();
            
            // Now delete the room
            $room->delete();
            return redirect()->route('rooms.index')->with('success', 'Sala excluída com sucesso!');
        }
        
        return redirect()->route('rooms.index')->with('error', 'Você não tem permissão para excluir esta sala.');
    }
    
    /**
     * Join a room.
     */
    public function join(Room $room)
    {
        // This method is deprecated as direct joining is no longer allowed
        // All users must request access and be approved by room owners
        return redirect()->route('rooms.index')->with('error', 'Acesso direto não é permitido. Por favor, solicite acesso ao proprietário da sala.');
    }
    
    /**
     * Request to join a room.
     */
    public function requestJoin(Room $room)
    {
        // Check if room is private
        if ($room->is_private) {
            return redirect()->route('rooms.index')->with('error', 'Salas privadas requerem convite direto do proprietário.');
        }
        
        // Check if already a member
        $user = Auth::user();
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        
        if ($isMember) {
            return redirect()->route('rooms.show', $room);
        }
        
        // Check if request already exists
        $existingRequest = RoomJoinRequest::where('user_id', Auth::id())
            ->where('room_id', $room->id)
            ->first();

        $roomOwner = User::find($room->created_by);
        $requester = Auth::user();

        if ($existingRequest) {
            if ($existingRequest->status === 'pending') {
                return redirect()->route('rooms.index')->with('info', 'Sua solicitação de acesso está pendente de aprovação pelo proprietário da sala.');
            } else {
                // Atualiza o status para pending
                $existingRequest->update(['status' => 'pending']);
                // Notifica o dono da sala
                if ($roomOwner) {
                    $roomOwner->notify(new \App\Notifications\RoomJoinRequestNotification($room, $requester));
                }
                return redirect()->route('rooms.index')->with('success', 'Sua solicitação de acesso foi reenviada ao proprietário da sala.');
            }
        }

        // Create join request
        RoomJoinRequest::create([
            'user_id' => Auth::id(),
            'room_id' => $room->id,
            'status' => 'pending'
        ]);
        // Notifica o dono da sala
        if ($roomOwner) {
            $roomOwner->notify(new \App\Notifications\RoomJoinRequestNotification($room, $requester));
        }
        
        return redirect()->route('rooms.index')->with('success', 'Sua solicitação de acesso foi enviada ao proprietário da sala e está aguardando aprovação.');
    }
    
    /**
     * Approve a join request.
     */
    public function approveJoinRequest(RoomJoinRequest $request)
    {
        // Check if user is admin of the room
        $room = $request->room;
        $user = Auth::user();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        if (!$isAdmin) {
            return redirect()->route('rooms.index')->with('error', 'You do not have permission to approve this request.');
        }
        
        // Update request status
        $request->update(['status' => 'approved']);
        
        // Add user to room
        UserRoom::create([
            'user_id' => $request->user_id,
            'room_id' => $room->id,
            'is_admin' => false
        ]);
        
        return redirect()->route('rooms.show', $room)->with('success', 'Join request approved.');
    }
    
    /**
     * Reject a join request.
     */
    public function rejectJoinRequest(RoomJoinRequest $request)
    {
        // Check if user is admin of the room
        $room = $request->room;
        $user = Auth::user();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        if (!$isAdmin) {
            return redirect()->route('rooms.index')->with('error', 'You do not have permission to reject this request.');
        }
        
        // Update request status
        $request->update(['status' => 'rejected']);
        
        return redirect()->route('rooms.show', $room)->with('success', 'Join request rejected.');
    }
    
    /**
     * Show pending join requests for a room.
     */
    public function showJoinRequests(Room $room)
    {
        // Check if user is admin of the room
        $user = Auth::user();
        $isAdmin = $user->rooms()->where('room_id', $room->id)->wherePivot('is_admin', true)->exists();
        
        if (!$isAdmin) {
            return redirect()->route('rooms.show', $room)->with('error', 'You do not have permission to view join requests.');
        }
        
        $pendingRequests = $room->joinRequests()
            ->where('status', 'pending')
            ->with('user')
            ->get();
        
        return view('rooms.join_requests', compact('room', 'pendingRequests'));
    }
    
    /**
     * Leave a room.
     */
    public function leave(Room $room)
    {
        // Check if creator (can't leave if creator)
        if ($room->created_by === Auth::id()) {
            return redirect()->route('rooms.show', $room)->with('error', 'As the creator, you cannot leave this room. You may delete it instead.');
        }
        
        // Remove user from room
        UserRoom::where('user_id', Auth::id())
                ->where('room_id', $room->id)
                ->delete();
        
        return redirect()->route('rooms.index')->with('success', 'You have left the room.');
    }

    /**
     * Get mentionable members for a room.
     */
    public function getMentionableMembers(Room $room)
    {
        $user = Auth::user();
        
        // Se a sala for privada, apenas membros podem ser mencionados
        if ($room->is_private) {
            $members = $room->members()
                ->where('users.id', '!=', $user->id) // Excluir o próprio usuário
                ->select('users.id', 'users.name')
                ->get();
        } else {
            // Se for pública, qualquer usuário pode ser mencionado
            $members = User::where('users.id', '!=', $user->id) // Excluir o próprio usuário
                ->select('users.id', 'users.name')
                ->get();
        }
        
        return response()->json($members);
    }
}
