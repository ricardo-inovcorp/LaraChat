@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1>{{ $room->name }}</h1>
        <p class="text-muted">{{ $room->description }}</p>
        @if($room->is_private)
            <span class="badge bg-info">Privada</span>
        @else
            <span class="badge bg-success">Pública</span>
        @endif
    </div>
    <div class="col-md-4 text-md-end">
        @if($isAdmin)
            <a href="{{ route('rooms.edit', $room) }}" class="btn btn-warning">Editar Sala</a>
        @endif
        
        @if($room->created_by == Auth::id())
            <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta sala?')">Excluir Sala</button>
            </form>
        @else
            <form action="{{ route('rooms.leave', $room) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-secondary">Sair da Sala</button>
            </form>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">Mensagens</div>
            <div class="card-body">
                <div class="chat-messages" style="height: 400px; overflow-y: auto;">
                    @if(count($messages) > 0)
                        @foreach($messages as $message)
                            <div class="message mb-3 {{ $message->user_id == Auth::id() ? 'text-end' : '' }}">
                                <div class="message-header">
                                    <strong>{{ $message->user->name }}</strong>
                                    <small class="text-muted">{{ $message->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="message-content p-2 {{ $message->user_id == Auth::id() ? 'bg-primary text-white' : 'bg-light' }}" style="border-radius: 10px; display: inline-block; max-width: 80%;">
                                    {{ $message->content }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center">Não há mensagens nesta sala ainda.</p>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                    <div class="input-group">
                        <input type="text" name="content" class="form-control" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">Membros ({{ count($members) }})</div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($members as $member)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $member->name }}
                            @if($member->pivot->is_admin)
                                <span class="badge bg-primary">Admin</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    const authId = "{{ Auth::id() }}";
    const roomId = "{{ $room->id }}";
    
    console.log('Chat room initialized, ID:', roomId);
    
    // Ativar logs do Pusher
    Pusher.logToConsole = true;
    
    // Criar instância do Pusher (igual ao exemplo)
    const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
        forceTLS: true
    });
    
    // Assinar o canal da sala
    const channelName = 'chat-room.' + roomId;
    console.log('Subscribing to channel:', channelName);
    const channel = pusher.subscribe(channelName);
    
    // Escutar por novos eventos de mensagem - usando bind() como no exemplo
    channel.bind('my-event', function(data) {
        console.log('Received new message:', data);
        
        // Criar elemento para a mensagem
        const messageDiv = document.createElement('div');
        messageDiv.className = data.user.id == authId ? 'message mb-3 text-end' : 'message mb-3';
        
        const messageHeader = document.createElement('div');
        messageHeader.className = 'message-header';
        messageHeader.innerHTML = '<strong>' + data.user.name + '</strong> <small class="text-muted">' + new Date().toLocaleString() + '</small>';
        
        const messageContent = document.createElement('div');
        messageContent.className = data.user.id == authId ? 'message-content p-2 bg-primary text-white' : 'message-content p-2 bg-light';
        messageContent.style.borderRadius = '10px';
        messageContent.style.display = 'inline-block';
        messageContent.style.maxWidth = '80%';
        messageContent.textContent = data.message;
        
        messageDiv.appendChild(messageHeader);
        messageDiv.appendChild(messageContent);
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
    
    // Interceptar o envio do formulário para mostrar a mensagem imediatamente
    const messageForm = document.querySelector('form[action*="messages"]');
    messageForm.addEventListener('submit', function(e) {
        const contentInput = this.querySelector('input[name="content"]');
        if (!contentInput || !contentInput.value.trim()) return;
        
        // Mostrar mensagem imediata para feedback do usuário
        const content = contentInput.value.trim();
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message mb-3 text-end';
        
        const messageHeader = document.createElement('div');
        messageHeader.className = 'message-header';
        messageHeader.innerHTML = '<strong>{{ Auth::user()->name }}</strong> <small class="text-muted">' + new Date().toLocaleString() + '</small>';
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content p-2 bg-primary text-white';
        messageContent.style.borderRadius = '10px';
        messageContent.style.display = 'inline-block';
        messageContent.style.maxWidth = '80%';
        messageContent.textContent = content;
        
        messageDiv.appendChild(messageHeader);
        messageDiv.appendChild(messageContent);
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
});
</script>
@endsection 