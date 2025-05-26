<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use App\Notifications\MessageReaction as MessageReactionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageReactionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Toggle a reaction on a message.
     */
    public function toggle(Request $request, Message $message)
    {
        // Obter o emoji do request
        $emoji = $request->input('emoji');
        
        Log::info('Toggle reaction request', [
            'message_id' => $message->id, 
            'user_id' => Auth::id(),
            'emoji' => $emoji,
            'request_method' => $request->method(),
            'all_data' => $request->all()
        ]);
        
        if (empty($emoji)) {
            return response()->json([
                'success' => false,
                'message' => 'O emoji é obrigatório.'
            ], 422);
        }
        
        // Validar o tamanho máximo do emoji
        if (mb_strlen($emoji) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'O emoji não pode ter mais de 10 caracteres.'
            ], 422);
        }
        
        $user = Auth::user();
        
        // Verificar se o usuário está tentando reagir à própria mensagem
        if ($message->user_id === $user->id) {
            Log::warning('User tried to react to their own message', [
                'user_id' => $user->id,
                'message_id' => $message->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Você não pode reagir às suas próprias mensagens.'
            ], 403);
        }
        
        Log::info('Processing reaction with emoji: ' . $emoji);
        
        try {
            // Verificar se o usuário já reagiu com este emoji
            $existingReaction = MessageReaction::where('message_id', $message->id)
                ->where('user_id', $user->id)
                ->where('emoji', $emoji)
                ->first();
            
            // Inicializar a variável de ação
            $action = '';
            
            if ($existingReaction) {
                // Se a reação já existe, remova-a (toggle off)
                $existingReaction->delete();
                $action = 'removed';
                Log::info('Reaction removed', ['reaction_id' => $existingReaction->id]);
            } else {
                // Se a reação não existe, adicione-a (toggle on)
                $reaction = MessageReaction::create([
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'emoji' => $emoji
                ]);
                $action = 'added';
                Log::info('Reaction added', ['reaction_id' => $reaction->id]);
                
                // Enviar notificação para o autor da mensagem
                $messageAuthor = User::find($message->user_id);
                if ($messageAuthor) {
                    $messageAuthor->notify(new MessageReactionNotification($message, $user, $emoji));
                    Log::info('Notification sent', [
                        'to_user_id' => $messageAuthor->id,
                        'from_user_id' => $user->id,
                        'message_id' => $message->id,
                        'emoji' => $emoji
                    ]);
                }
            }
            
            // Obter todas as reações atualizadas para esta mensagem
            $reactions = $this->getFormattedReactions($message);
            
            return response()->json([
                'success' => true,
                'action' => $action,
                'reactions' => $reactions
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing reaction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a reação: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all reactions for a message.
     */
    public function getReactions(Message $message)
    {
        Log::info('Getting reactions for message', ['message_id' => $message->id]);
        $reactions = $this->getFormattedReactions($message);
        
        return response()->json([
            'success' => true,
            'reactions' => $reactions
        ]);
    }
    
    /**
     * Helper method to format reactions for response.
     */
    private function getFormattedReactions(Message $message)
    {
        $reactions = $message->reactions()
            ->with('user:id,name')
            ->get()
            ->groupBy('emoji')
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'users' => $group->pluck('user')
                ];
            });
            
        return $reactions;
    }
}
