<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\UserRoom;
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
        // Get all public rooms
        $publicRooms = Room::where('is_private', false)->get();
        
        // Get user's private rooms
        $user = Auth::user();
        $userRooms = $user->rooms()->where('is_private', true)->get();
        
        return view('rooms.index', compact('publicRooms', 'userRooms'));
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id'
        ]);
        
        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'is_private' => $request->is_private ?? false
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
            }
        }
        
        return redirect()->route('rooms.show', $room)->with('success', 'Room created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        // Check if user is member of the room
        $user = Auth::user();
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        
        if (!$isMember && $room->is_private) {
            return redirect()->route('rooms.index')->with('error', 'You do not have access to this room.');
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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
            }
        }
        
        return redirect()->route('rooms.show', $room)->with('success', 'Room updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        // Check if user is the creator of the room
        if ($room->created_by !== Auth::id()) {
            return redirect()->route('rooms.index')->with('error', 'You do not have permission to delete this room.');
        }
        
        $room->delete();
        
        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully!');
    }
    
    /**
     * Join a room.
     */
    public function join(Room $room)
    {
        // Check if room is private
        if ($room->is_private) {
            return redirect()->route('rooms.index')->with('error', 'You cannot join a private room without an invitation.');
        }
        
        // Check if already a member
        $user = Auth::user();
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        
        if ($isMember) {
            return redirect()->route('rooms.show', $room);
        }
        
        // Join the room
        UserRoom::create([
            'user_id' => Auth::id(),
            'room_id' => $room->id,
            'is_admin' => false
        ]);
        
        return redirect()->route('rooms.show', $room)->with('success', 'You have joined the room!');
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
}
