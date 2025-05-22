<?php

use App\Models\Room;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privado para mensagens diretas entre usuários
Broadcast::channel('chat.{userA}.{userB}', function ($user, $userA, $userB) {
    Log::debug('Autenticando canal chat', ['userA' => $userA, 'userB' => $userB, 'user' => $user->id]);
    return (int) $user->id === (int) $userA || (int) $user->id === (int) $userB;
});

// Canal usado para mensagens de sala
Broadcast::channel('chat-room.{roomId}', function ($user, $roomId) {
    Log::debug('Autenticando canal chat-room', ['roomId' => $roomId, 'user' => $user->id]);
    
    try {
        $room = Room::findOrFail($roomId);
        
        // Se a sala for pública, qualquer usuário pode acessar
        if (!$room->is_private) {
            Log::debug('Sala pública, autorizando acesso');
            return ['id' => $user->id, 'name' => $user->name];
        }
        
        // Se a sala for privada, apenas membros podem acessar
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        Log::debug('Verificação de membro', ['isMember' => $isMember]);
        return $isMember ? ['id' => $user->id, 'name' => $user->name] : false;
    } catch (\Exception $e) {
        Log::error('Erro na autenticação do canal chat-room', ['error' => $e->getMessage()]);
        return false;
    }
});

// Canal de presença para salas
Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    Log::debug('Autenticando canal presence-room', ['roomId' => $roomId, 'user' => $user->id]);
    
    try {
        $room = Room::findOrFail($roomId);
        
        // Se a sala for pública, qualquer usuário pode acessar
        if (!$room->is_private) {
            Log::debug('Sala pública, autorizando acesso');
            return ['id' => $user->id, 'name' => $user->name];
        }
        
        // Se a sala for privada, apenas membros podem acessar
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        Log::debug('Verificação de membro', ['isMember' => $isMember]);
        return $isMember ? ['id' => $user->id, 'name' => $user->name] : false;
    } catch (\Exception $e) {
        Log::error('Erro na autenticação do canal presence-room', ['error' => $e->getMessage()]);
        return false;
    }
});

// Compatibilidade com canal room sem prefixo
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    Log::debug('Autenticando canal room (sem prefixo)', ['roomId' => $roomId, 'user' => $user->id]);
    
    try {
        $room = Room::findOrFail($roomId);
        
        // Se a sala for pública, qualquer usuário pode acessar
        if (!$room->is_private) {
            Log::debug('Sala pública, autorizando acesso (sem prefixo)');
            return ['id' => $user->id, 'name' => $user->name];
        }
        
        // Se a sala for privada, apenas membros podem acessar
        $isMember = $user->rooms()->where('room_id', $room->id)->exists();
        Log::debug('Verificação de membro (sem prefixo)', ['isMember' => $isMember]);
        return $isMember ? ['id' => $user->id, 'name' => $user->name] : false;
    } catch (\Exception $e) {
        Log::error('Erro na autenticação do canal room (sem prefixo)', ['error' => $e->getMessage()]);
        return false;
    }
}); 