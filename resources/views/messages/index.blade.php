@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1>Minhas Mensagens</h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('users.index') }}" class="btn btn-primary">Nova Mensagem</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Conversas</div>
            <div class="card-body p-0">
                @if ($users->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($users as $user)
                            @php
                                $latestMessage = $directMessages[$user->id]->first();
                                $unreadCount = $directMessages[$user->id]->where('receiver_id', Auth::id())->where('is_read', false)->count();
                            @endphp
                            <a href="{{ route('messages.conversation', $user) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3 position-relative">
                                        @if($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="avatar-initials bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="position-absolute bottom-0 end-0 translate-middle-y">
                                            <span class="badge rounded-circle p-1 {{ $user->isOnline() ? 'bg-success' : 'bg-secondary' }}" 
                                                  style="width: 12px; height: 12px;" 
                                                  title="{{ $user->isOnline() ? 'Online' : 'Offline' }}">
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <h5 class="mb-1 me-2">{{ $user->name }}</h5>
                                            <small class="text-{{ $user->isOnline() ? 'success' : 'muted' }}">
                                                {{ $user->isOnline() ? 'Online' : 'Offline' }}
                                            </small>
                                        </div>
                                        <p class="mb-1 text-muted text-truncate" style="max-width: 300px;">
                                            @if($latestMessage->user_id == Auth::id())
                                                <span class="text-muted">Você: </span>
                                            @endif
                                            {{ $latestMessage->content }}
                                        </p>
                                        <small class="text-muted">{{ $latestMessage->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                @if($unreadCount > 0)
                                    <span class="badge bg-primary rounded-pill">{{ $unreadCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center p-4">
                        <p class="mb-0">Você ainda não tem conversas.</p>
                        <p>Comece uma nova conversa com alguém!</p>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary">Iniciar Nova Conversa</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 