@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<!-- Meta tag para CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

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
                            <div class="message-wrapper mb-3 {{ $message->user_id == Auth::id() ? 'text-end' : '' }}">
                                <div class="message" data-message-id="{{ $message->id }}">
                                    <div class="message-header">
                                        <strong>{{ $message->user->name }}</strong>
                                        <small class="text-muted">{{ $message->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="message-content p-2 {{ $message->user_id == Auth::id() ? 'bg-primary text-white' : 'bg-light' }}" style="border-radius: 10px; display: inline-block; max-width: 80%;">
                                        {{ $message->content }}
                                    </div>
                                    
                                    <!-- Reação à mensagem -->
                                    <div class="message-reactions mt-1 {{ $message->user_id == Auth::id() ? 'justify-content-end' : '' }}">
                                        <div class="d-flex reaction-container flex-wrap" data-message-id="{{ $message->id }}">
                                            <!-- As reações existentes serão carregadas via JavaScript -->
                                        </div>
                                        
                                        <!-- Só mostrar opções de reação para mensagens de outros usuários -->
                                        @if($message->user_id != Auth::id())
                                        <div class="emoji-controls mt-1">
                                            <!-- Botão ADD -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary add-reaction-btn" data-message-id="{{ $message->id }}">
                                                <i class="bi bi-emoji-smile"></i> Add
                                            </button>
                                            
                                            <!-- Emoji options (inicialmente escondido) -->
                                            <div class="emoji-options d-none" data-message-id="{{ $message->id }}">
                                                @foreach(['👍', '👎', '❤️', '😂', '😮', '😢', '🎉', '🔥'] as $emoji)
                                                    <form method="POST" action="{{ route('messages.reactions.toggle', $message->id) }}" class="d-inline emoji-form">
                                                        @csrf
                                                        <input type="hidden" name="emoji" value="{{ $emoji }}">
                                                        <button type="submit" class="emoji-btn">{{ $emoji }}</button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </div>
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
        const messageWrapper = document.createElement('div');
        messageWrapper.className = data.user.id == authId ? 'message-wrapper mb-3 text-end' : 'message-wrapper mb-3';
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
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
        
        // Adicionar container para reações
        const reactionsContainer = document.createElement('div');
        reactionsContainer.className = data.user.id == authId ? 'message-reactions mt-1 justify-content-end' : 'message-reactions mt-1';
        
        const reactionList = document.createElement('div');
        reactionList.className = 'd-flex reaction-container flex-wrap';
        reactionList.setAttribute('data-message-id', data.message_id);
        
        reactionsContainer.appendChild(reactionList);
        
        // Só adicionar controles de emoji se não for mensagem do usuário atual
        if (data.user.id != authId) {
            const emojiControls = document.createElement('div');
            emojiControls.className = 'emoji-controls mt-1';
            
            // Botão ADD
            const addButton = document.createElement('button');
            addButton.type = 'button';
            addButton.className = 'btn btn-sm btn-outline-secondary add-reaction-btn';
            addButton.setAttribute('data-message-id', data.message_id);
            addButton.innerHTML = '<i class="bi bi-emoji-smile"></i> Add';
            
            // Opções de emoji (escondidas inicialmente)
            const emojiOptions = document.createElement('div');
            emojiOptions.className = 'emoji-options d-none';
            emojiOptions.setAttribute('data-message-id', data.message_id);
            
            const emojis = ['👍', '👎', '❤️', '😂', '😮', '😢', '🎉', '🔥'];
            
            emojis.forEach(emoji => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ url('messages') }}/${data.message_id}/reactions`;
                form.className = 'd-inline emoji-form';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                
                const emojiInput = document.createElement('input');
                emojiInput.type = 'hidden';
                emojiInput.name = 'emoji';
                emojiInput.value = emoji;
                
                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'emoji-btn';
                button.textContent = emoji;
                
                form.appendChild(csrfInput);
                form.appendChild(emojiInput);
                form.appendChild(button);
                emojiOptions.appendChild(form);
            });
            
            emojiControls.appendChild(addButton);
            emojiControls.appendChild(emojiOptions);
            reactionsContainer.appendChild(emojiControls);
        }
        
        messageDiv.appendChild(messageHeader);
        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(reactionsContainer);
        
        messageWrapper.appendChild(messageDiv);
        chatMessages.appendChild(messageWrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Carregar reações para a nova mensagem
        loadReactions(data.message_id);
        // Configurar os listeners de eventos para os novos elementos
        setupEventListeners();
    }
    
    // Escutar por novas mensagens em ambos os canais
    channel1.bind('new-message', processNewMessage);
    channel2.bind('new-message', processNewMessage);
    
    // Sistema de reações
    
    // Configurar event listeners
    function setupEventListeners() {
        // Botões "Add Reaction"
        document.querySelectorAll('.add-reaction-btn').forEach(button => {
            button.removeEventListener('click', toggleEmojiOptions);
            button.addEventListener('click', toggleEmojiOptions);
        });
        
        // Formulários de emoji
        document.querySelectorAll('.emoji-form').forEach(form => {
            form.removeEventListener('submit', handleEmojiFormSubmit);
            form.addEventListener('submit', handleEmojiFormSubmit);
        });
    }
    
    // Toggle das opções de emoji
    function toggleEmojiOptions(event) {
        event.preventDefault();
        
        const messageId = this.getAttribute('data-message-id');
        const emojiOptions = document.querySelector(`.emoji-options[data-message-id="${messageId}"]`);
        
        // Fechar todos os outros emoji options primeiro
        document.querySelectorAll('.emoji-options').forEach(option => {
            if (option !== emojiOptions) {
                option.classList.add('d-none');
            }
        });
        
        // Toggle do atual
        emojiOptions.classList.toggle('d-none');
    }
    
    // Manipulador de formulário de emoji
    function handleEmojiFormSubmit(event) {
        event.preventDefault();
        
        const form = event.currentTarget;
        const url = form.action;
        const formData = new FormData(form);
        
        // Mostrar indicador de carregamento
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '...';
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botão
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            
            if (data.success) {
                const messageId = form.closest('.emoji-options').getAttribute('data-message-id');
                displayReactions(messageId, data.reactions);
                
                // Fechar o emoji options após selecionar
                form.closest('.emoji-options').classList.add('d-none');
            } else if (data.message) {
                alert(data.message);
            }
        })
        .catch(error => {
            // Restaurar botão
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            
            console.error('Error processing reaction:', error);
            alert('Erro ao processar sua reação. Por favor, tente novamente.');
        });
    }
    
    // Carregar reações existentes para todas as mensagens
    function loadAllReactions() {
        document.querySelectorAll('.message').forEach(messageElement => {
            const messageId = messageElement.getAttribute('data-message-id');
            loadReactions(messageId);
        });
    }
    
    // Carregar reações para uma mensagem específica
    function loadReactions(messageId) {
        fetch(`{{ url('messages') }}/${messageId}/reactions/list`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReactions(messageId, data.reactions);
                }
            })
            .catch(error => console.error('Error fetching reactions:', error));
    }
    
    // Exibir reações na interface
    function displayReactions(messageId, reactions) {
        const container = document.querySelector(`.reaction-container[data-message-id="${messageId}"]`);
        if (!container) {
            console.error(`Container para reações não encontrado: messageId=${messageId}`);
            return;
        }
        
        container.innerHTML = '';
        
        console.log('Displaying reactions for message:', messageId, reactions);
        
        // Verificar se há reações para exibir
        if (Object.keys(reactions).length === 0) {
            console.log('Não há reações para esta mensagem ainda');
            return;
        }
        
        // Converter o objeto em array para facilitar a iteração
        Object.entries(reactions).forEach(([emoji, data]) => {
            console.log('Processando emoji:', emoji, 'dados:', data);
            
            const reactionButton = document.createElement('button');
            reactionButton.className = 'btn btn-sm btn-light reaction-btn me-1 mb-1';
            reactionButton.innerHTML = `${emoji} <span class="reaction-count">${data.count}</span>`;
            reactionButton.setAttribute('data-emoji', emoji);
            reactionButton.setAttribute('data-message-id', messageId);
            
            // Verificar se o usuário atual reagiu com este emoji
            const userReacted = data.users.some(user => user.id == authId);
            if (userReacted) {
                reactionButton.classList.add('active', 'btn-primary');
                reactionButton.classList.remove('btn-light');
                
                // Adicionar evento para remover a reação quando clicado
                reactionButton.addEventListener('click', function() {
                    removeReaction(messageId, emoji);
                });
            }
            
            // Adicionar tooltip com os nomes dos usuários que reagiram
            const userNames = data.users.map(user => user.name).join(', ');
            reactionButton.setAttribute('title', userNames);
            
            container.appendChild(reactionButton);
        });
    }
    
    // Função para remover uma reação
    function removeReaction(messageId, emoji) {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('emoji', emoji);
        
        fetch(`{{ url('messages') }}/${messageId}/reactions`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReactions(messageId, data.reactions);
            } else if (data.message) {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error removing reaction:', error);
            alert('Erro ao remover sua reação. Por favor, tente novamente.');
        });
    }
    
    // Inicializar
    loadAllReactions();
    setupEventListeners();
    
    // Fechar emoji options ao clicar fora
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.add-reaction-btn') && !event.target.closest('.emoji-options')) {
            document.querySelectorAll('.emoji-options').forEach(option => {
                option.classList.add('d-none');
            });
        }
    });
});
</script>

<!-- Bootstrap Icons (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Estilos adicionais -->
<style>
.message-wrapper {
    position: relative;
    margin-bottom: 1rem;
}
.message {
    display: inline-block;
    max-width: 85%;
}
.message-reactions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.message-wrapper.text-end .message-reactions {
    align-items: flex-end;
}
.reaction-container {
    margin-top: 0.25rem;
}
.reaction-btn {
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
}
.reaction-btn.active {
    background-color: #0d6efd;
    color: white;
}
.emoji-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    margin: 0.2rem;
    padding: 0.2rem 0.4rem;
    cursor: pointer;
    transition: transform 0.1s;
}
.emoji-btn:hover {
    transform: scale(1.2);
}
.emoji-options {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 0.4rem;
    margin-top: 0.3rem;
}
.emoji-form {
    display: inline-block;
}
</style>
@endsection 