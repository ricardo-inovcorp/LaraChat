@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1>Salas de Chat</h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('rooms.create') }}" class="btn btn-primary">Criar Nova Sala</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Salas Públicas</div>
            <div class="card-body">
                @if ($publicRooms->count() > 0)
                    <ul class="list-group">
                        @foreach ($publicRooms as $room)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    @if(in_array($room->id, $userRoomIds))
                                        <a href="{{ route('rooms.show', $room) }}">{{ $room->name }}</a>
                                    @else
                                        <span class="fw-medium">{{ $room->name }}</span>
                                    @endif
                                    <small class="text-muted d-block">{{ $room->description }}</small>
                                    <small class="text-muted">
                                        Owner: {{ $room->creator->name }}
                                    </small>
                                </div>
                                <div>
                                    @if(in_array($room->id, $userRoomIds))
                                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-outline-primary">Acessar</a>
                                    @elseif(in_array($room->id, $pendingRequests))
                                        <span class="badge bg-warning">Solicitação Pendente</span>
                                    @else
                                        <div class="btn-group">
                                            <form action="{{ route('rooms.request-join', $room) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Solicitar</button>
                                            </form>
                                        </div>
                                    @endif
                                    
                                    @if(Auth::user()->isAdmin() || $room->created_by == Auth::id())
                                        <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="d-inline mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta sala?')">Excluir</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-center">Não há salas públicas disponíveis.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Salas Privadas</div>
            <div class="card-body">
                @if ($userRooms->count() > 0)
                    <ul class="list-group">
                        @foreach ($userRooms as $room)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('rooms.show', $room) }}">{{ $room->name }}</a>
                                    <small class="text-muted d-block">{{ $room->description }}</small>
                                    <div>
                                        <span class="badge bg-info">Privada</span>
                                        <small class="text-muted">Owner: {{ $room->creator->name }}</small>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-outline-primary">Acessar</a>
                                    
                                    @if(Auth::user()->isAdmin() || $room->created_by == Auth::id())
                                        <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="d-inline mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta sala?')">Excluir</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-center">Você não participa de nenhuma sala privada.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 