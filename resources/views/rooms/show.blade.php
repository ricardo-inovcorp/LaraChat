@extends('layouts.app')

@section('content')
<!-- Meta tag para CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- CSS personalizado para o chat -->
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">

<div class="row mb-4">
    <div class="col-md-8">
        <h2>{{ $room->name }}</h2>
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
            
            @if($room->created_by == Auth::id() && !$room->is_private)
                @php
                    $pendingCount = $room->joinRequests()->where('status', 'pending')->count();
                @endphp
                <a href="{{ route('rooms.join-requests', $room) }}" class="btn btn-info position-relative">
                    Solicitações de Acesso
                    @if($pendingCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </a>
            @endif
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
                            <div class="message-wrapper mb-3 {{ $message->user_id == Auth::id() ? 'text-end' : '' }}">
                                <div class="message d-flex {{ $message->user_id == Auth::id() ? 'flex-row-reverse' : 'flex-row' }}" data-message-id="{{ $message->id }}">
                                    <!-- Avatar do usuário -->
                                    <div class="message-avatar {{ $message->user_id == Auth::id() ? 'ms-2' : 'me-2' }}">
                                        @if($message->user->avatar)
                                            <img src="{{ $message->user->avatar }}" alt="{{ $message->user->name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="avatar-initials bg-{{ $message->user_id == Auth::id() ? 'primary' : 'secondary' }} text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                {{ substr($message->user->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Conteúdo da mensagem -->
                                    <div class="message-content-wrapper">
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
                            </div>
                        @endforeach
                    @else
                        <p class="text-center">Não há mensagens nesta sala ainda.</p>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('messages.store') }}" method="POST" class="position-relative">
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                    <div class="input-group">
                        <input type="text" name="content" id="messageInput" class="form-control" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                    <div id="mentionSuggestions" class="mention-suggestions d-none">
                        <ul class="list-group"></ul>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">Membros ({{ count($members) }})</div>
            <div class="card-body p-2">
                <ul class="list-group members-list">
                    @foreach($members as $member)
                        <li class="list-group-item d-flex align-items-center">
                            <div class="member-status {{ $member->isOnline() ? 'online' : 'offline' }}"></div>
                            
                            <div class="member-avatar">
                                @if($member->avatar)
                                    <img src="{{ $member->avatar }}" alt="{{ $member->name }}">
                                @else
                                    <div class="member-avatar-initials">{{ substr($member->name, 0, 1) }}</div>
                                @endif
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                <span>{{ $member->name }}</span>
                                @if($member->id == $room->created_by)
                                    <span class="badge bg-primary">Owner</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Dados do usuário para JavaScript -->
<script>
    // Inicializa dados do usuário atual
    window.currentUser = {
        id: {{ Auth::id() }},
        name: "{{ Auth::user()->name }}",
        avatar: "{{ Auth::user()->avatar ? (filter_var(Auth::user()->avatar, FILTER_VALIDATE_URL) ? Auth::user()->avatar : asset('storage/' . Auth::user()->avatar)) : '' }}",
        initial: "{{ substr(Auth::user()->name, 0, 1) }}"
    };
    
    window.appData = {
        roomId: {{ $room->id }},
        pusherKey: "{{ env('PUSHER_APP_KEY') }}",
        pusherCluster: "{{ env('PUSHER_APP_CLUSTER') }}",
        csrf: "{{ csrf_token() }}"
    };
</script>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    const authId = currentUser.id;
    const roomId = appData.roomId;
    
    console.log('Chat room initialized, ID:', roomId);
    
    // Ativar logs do Pusher
    Pusher.logToConsole = true;
    
    // Criar instância do Pusher
    const pusher = new Pusher(appData.pusherKey, {
        cluster: appData.pusherCluster,
        forceTLS: true
    });
    
    // Assinar o canal da sala
    const channelName = 'chat-room.' + roomId;
    console.log('Subscribing to channel:', channelName);
    const channel = pusher.subscribe(channelName);
    
    // Função para processar menções no conteúdo da mensagem
    function processMentions(content) {
        return content.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
    }
    
    // Função para criar elemento de mensagem
    function createMessageElement(data) {
        const messageWrapper = document.createElement('div');
        messageWrapper.className = `message-wrapper mb-3 ${data.user.id == authId ? 'text-end' : ''}`;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message d-flex ${data.user.id == authId ? 'flex-row-reverse' : 'flex-row'}`;
        messageDiv.dataset.messageId = data.message_id;
        
        // Avatar
        const avatarDiv = document.createElement('div');
        avatarDiv.className = `message-avatar ${data.user.id == authId ? 'ms-2' : 'me-2'}`;
        avatarDiv.innerHTML = `<div class="avatar-initials bg-${data.user.id == authId ? 'primary' : 'secondary'} text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">${data.user.name.charAt(0)}</div>`;
        
        // Conteúdo
        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'message-content-wrapper';
        
        const header = document.createElement('div');
        header.className = 'message-header';
        header.innerHTML = `<strong>${data.user.name}</strong><small class="text-muted">${new Date(data.created_at).toLocaleString()}</small>`;
        
        const content = document.createElement('div');
        content.className = `message-content p-2 ${data.user.id == authId ? 'bg-primary text-white' : 'bg-light'}`;
        content.style.borderRadius = '10px';
        content.style.display = 'inline-block';
        content.style.maxWidth = '80%';
        content.innerHTML = processMentions(data.message);
        
        contentWrapper.appendChild(header);
        contentWrapper.appendChild(content);
        
        messageDiv.appendChild(avatarDiv);
        messageDiv.appendChild(contentWrapper);
        messageWrapper.appendChild(messageDiv);
        
        return messageWrapper;
    }
    
    // Escutar por novos eventos de mensagem
    channel.bind('my-event', function(data) {
        console.log('Nova mensagem recebida:', data);
        
        const messageElement = createMessageElement(data);
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Se o usuário atual foi mencionado, destacar a mensagem
        if (data.mentions && data.mentions.includes(authId)) {
            messageElement.classList.add('mentioned');
            messageElement.style.backgroundColor = 'rgba(255, 193, 7, 0.1)';
            messageElement.style.borderRadius = '5px';
            messageElement.style.padding = '5px';
        }
    });
    
    // Estilizar menções
    const style = document.createElement('style');
    style.textContent = `
        .mention {
            color: #007bff;
            font-weight: bold;
        }
        .mentioned {
            animation: highlight 2s ease-out;
        }
        @keyframes highlight {
            0% { background-color: rgba(255, 193, 7, 0.3); }
            100% { background-color: transparent; }
        }
    `;
    document.head.appendChild(style);
    
    // Autocomplete de menções
    const messageInput = document.getElementById('messageInput');
    const mentionSuggestions = document.getElementById('mentionSuggestions');
    const suggestionsList = mentionSuggestions.querySelector('ul');
    let mentionableMembers = [];
    let currentMentionStart = -1;
    let selectedIndex = -1;
    
    // Carregar membros mencionáveis
    fetch(`{{ route('rooms.mentionable-members', $room) }}`)
        .then(response => response.json())
        .then(members => {
            mentionableMembers = members;
        });
    
    // Função para filtrar membros baseado no texto digitado
    function filterMembers(text) {
        return mentionableMembers.filter(member => 
            member.name.toLowerCase().includes(text.toLowerCase())
        );
    }
    
    // Função para mostrar sugestões
    function showSuggestions(text) {
        const filteredMembers = filterMembers(text);
        
        if (filteredMembers.length === 0) {
            mentionSuggestions.classList.add('d-none');
            return;
        }
        
        suggestionsList.innerHTML = '';
        filteredMembers.forEach((member, index) => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `@${member.name}`;
            li.dataset.index = index;
            suggestionsList.appendChild(li);
        });
        
        mentionSuggestions.classList.remove('d-none');
        
        // Ajustar posição da lista de sugestões
        const inputRect = messageInput.getBoundingClientRect();
        const suggestionsRect = mentionSuggestions.getBoundingClientRect();
        
        // Se a lista estiver saindo da tela por baixo, mostrar acima do input
        if (inputRect.bottom + suggestionsRect.height > window.innerHeight) {
            mentionSuggestions.style.bottom = 'calc(100% + 5px)';
            mentionSuggestions.style.top = 'auto';
        } else {
            mentionSuggestions.style.top = 'calc(100% + 5px)';
            mentionSuggestions.style.bottom = 'auto';
        }
        
        selectedIndex = -1;
    }
    
    // Função para inserir menção
    function insertMention(member) {
        const beforeMention = messageInput.value.substring(0, currentMentionStart);
        const afterMention = messageInput.value.substring(messageInput.selectionStart);
        messageInput.value = `${beforeMention}@${member.name} ${afterMention}`;
        mentionSuggestions.classList.add('d-none');
        
        // Posicionar cursor após a menção
        const newPosition = currentMentionStart + member.name.length + 2; // +2 para o @ e o espaço
        messageInput.setSelectionRange(newPosition, newPosition);
        messageInput.focus();
    }
    
    // Evento de input para detectar digitação de @
    messageInput.addEventListener('input', function(e) {
        const text = this.value;
        const lastAtIndex = text.lastIndexOf('@', this.selectionStart);
        
        if (lastAtIndex !== -1) {
            const textAfterAt = text.substring(lastAtIndex + 1, this.selectionStart);
            if (!textAfterAt.includes(' ')) {
                currentMentionStart = lastAtIndex;
                showSuggestions(textAfterAt);
                return;
            }
        }
        
        mentionSuggestions.classList.add('d-none');
    });
    
    // Evento de clique nas sugestões
    suggestionsList.addEventListener('click', function(e) {
        const li = e.target.closest('.list-group-item');
        if (li) {
            const index = parseInt(li.dataset.index);
            insertMention(mentionableMembers[index]);
        }
    });
    
    // Navegação com teclado
    messageInput.addEventListener('keydown', function(e) {
        if (mentionSuggestions.classList.contains('d-none')) return;
        
        const items = suggestionsList.querySelectorAll('.list-group-item');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                break;
            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0) {
                    insertMention(mentionableMembers[selectedIndex]);
                }
                break;
            case 'Escape':
                mentionSuggestions.classList.add('d-none');
                break;
        }
        
        // Atualizar seleção visual
        items.forEach((item, index) => {
            item.classList.toggle('active', index === selectedIndex);
        });
    });
    
    // Fechar sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!messageInput.contains(e.target) && !mentionSuggestions.contains(e.target)) {
            mentionSuggestions.classList.add('d-none');
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
.mention-suggestions {
    position: absolute;
    bottom: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
}
.mention-suggestions .list-group-item {
    cursor: pointer;
    padding: 0.5rem 1rem;
    border: none;
    border-bottom: 1px solid #dee2e6;
}
.mention-suggestions .list-group-item:last-child {
    border-bottom: none;
}
.mention-suggestions .list-group-item:hover {
    background-color: #f8f9fa;
}
.mention-suggestions .list-group-item.active {
    background-color: #0d6efd;
    color: white;
}
.card-footer {
    position: relative;
    z-index: 1040;
}
</style>
@endsection 