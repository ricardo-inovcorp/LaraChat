@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1>Conversa com {{ $user->name }}</h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('messages.index') }}" class="btn btn-secondary">Voltar para Mensagens</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <div class="user-avatar me-3">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                    @else
                        <div class="avatar-initials bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div>
                    <h5 class="mb-0">{{ $user->name }}</h5>
                    <small class="text-muted">
                        @if($user->isOnline())
                            <span class="text-success">Online</span>
                        @else
                            <span>Offline</span>
                        @endif
                    </small>
                </div>
            </div>
            <div class="card-body">
                <div class="chat-messages" style="height: 400px; overflow-y: auto;">
                    @if(count($messages) > 0)
                        @foreach($messages as $message)
                            <div class="message mb-3 {{ $message->user_id == Auth::id() ? 'text-end' : '' }}" data-message-id="{{ $message->id }}">
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
                        <p class="text-center">Não há mensagens nesta conversa ainda. Envie a primeira mensagem!</p>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                    <div class="input-group">
                        <input type="text" name="content" class="form-control" placeholder="Digite sua mensagem..." required autofocus>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Configuração do Pusher para mensagens em tempo real
    const authId = "{{ Auth::id() }}";
    const receiverId = "{{ $user->id }}";
    
    // Ativar logs do Pusher
    Pusher.logToConsole = true;
    
    // Criar instância do Pusher
    const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
        forceTLS: true
    });
    
    // Precisamos escutar em ambos os canais possíveis para esta conversa
    // Canal 1: quando o usuário atual é o remetente
    const senderChannel = 'chat.' + authId + '.' + receiverId;
    // Canal 2: quando o usuário atual é o destinatário
    const receiverChannel = 'chat.' + receiverId + '.' + authId;
    
    console.log('Subscribing to channels:', senderChannel, receiverChannel);
    
    // Assinar ambos os canais
    const channel1 = pusher.subscribe(senderChannel);
    const channel2 = pusher.subscribe(receiverChannel);
    
    // Função para processar mensagens recebidas
    function processNewMessage(data) {
        console.log('Received new private message:', data);
        
        // Verificar se a mensagem já existe no DOM (para evitar duplicação)
        const existingMessage = document.querySelector(`[data-message-id="${data.message_id}"]`);
        if (existingMessage) {
            console.log('Message already exists in DOM, skipping');
            return;
        }
        
        // Criar elemento para a mensagem
        const messageDiv = document.createElement('div');
        messageDiv.className = data.user.id == authId ? 'message mb-3 text-end' : 'message mb-3';
        messageDiv.setAttribute('data-message-id', data.message_id);
        
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
    }
    
    // Escutar por novas mensagens em ambos os canais
    channel1.bind('new-message', processNewMessage);
    channel2.bind('new-message', processNewMessage);
});
</script>
@endsection 