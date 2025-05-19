<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of users (except the current user).
     */
    public function index(Request $request)
    {
        $query = $request->input('query');
        
        $users = User::where('id', '!=', Auth::id())
                    ->when($query, function($q) use ($query) {
                        return $q->where('name', 'like', "%{$query}%")
                                ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->orderBy('name', 'asc')
                    ->paginate(20);
        
        return view('users.index', compact('users', 'query'));
    }
    
    /**
     * Show profile for a user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }
    
    /**
     * Show current user's profile.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('users.profile', compact('user'));
    }
    
    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        
        $user->save();
        
        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
