<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $supabaseStorage;
    
    /**
     * Create a new controller instance.
     */
    public function __construct(SupabaseStorageService $supabaseStorage)
    {
        $this->middleware('auth');
        $this->supabaseStorage = $supabaseStorage;
    }
    
    /**
     * Display a listing of users (except the current user).
     */
    public function index(Request $request)
    {
        // Verificar se o usuário atual é admin
        $user = Auth::user();
        if (!$user->permissions === 'admin' && !$request->has('search_only')) {
            return redirect()->route('home')->with('error', 'Você não tem permissão para acessar esta página.');
        }
        
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
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        
        // Processar avatar se enviado
        if ($request->hasFile('avatar')) {
            try {
                $avatarFile = $request->file('avatar');
                
                // Log para depuração
                Log::info('Processando upload de avatar', [
                    'filename' => $avatarFile->getClientOriginalName(),
                    'size' => $avatarFile->getSize(),
                    'mime' => $avatarFile->getMimeType()
                ]);
                
                // Se já existir um avatar, tentar excluí-lo do Supabase
                if ($user->avatar && !empty($user->avatar)) {
                    try {
                        // Extrair o caminho do arquivo da URL completa
                        $avatarUrl = $user->avatar;
                        Log::info('Avatar existente', ['url' => $avatarUrl]);
                        
                        // Extrair o nome do arquivo da URL
                        $pathArray = parse_url($avatarUrl);
                        if (isset($pathArray['path'])) {
                            $pathParts = explode('/', $pathArray['path']);
                            $filename = end($pathParts);
                            
                            // Tentar excluir o arquivo do Supabase
                            if ($filename) {
                                Log::info('Tentando excluir avatar antigo', ['filename' => $filename]);
                                $this->supabaseStorage->delete($filename);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erro ao excluir avatar antigo: ' . $e->getMessage());
                        // Continuar mesmo se houver erro na exclusão
                    }
                }
                
                // Fazer upload do novo avatar para o Supabase
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
                $avatarUrl = $this->supabaseStorage->upload(
                    $avatarFile, 
                    'users', 
                    $filename
                );
                
                if ($avatarUrl) {
                    Log::info('Avatar URL obtida', ['url' => $avatarUrl]);
                    $user->avatar = $avatarUrl;
                } else {
                    Log::error('Falha ao obter URL do avatar');
                }
            } catch (\Exception $e) {
                Log::error('Erro no upload do avatar: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', 'Não foi possível fazer upload do avatar: ' . $e->getMessage());
            }
        }
        
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        
        $user->save();
        
        return redirect()->route('profile')->with('success', 'Perfil atualizado com sucesso!');
    }
    
    /**
     * Atualizar status e permissões de um usuário (admin only).
     */
    public function updateUserStatus(Request $request, User $user)
    {
        // Verificar se o usuário atual é admin
        if (!(Auth::user()->permissions === 'admin')) {
            return redirect()->back()->with('error', 'Você não tem permissão para esta ação.');
        }
        
        $request->validate([
            'permissions' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive',
        ]);
        
        $user->permissions = $request->permissions;
        $user->status = $request->status;
        $user->save();
        
        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
    }
}
