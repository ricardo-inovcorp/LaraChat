@extends('layouts.app')

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
                                    <a href="{{ route('rooms.show', $room) }}">{{ $room->name }}</a>
                                    <small class="text-muted d-block">{{ $room->description }}</small>
                                </div>
                                <div>
                                    <form action="{{ route('rooms.join', $room) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Entrar</button>
                                    </form>
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
            <div class="card-header">Minhas Salas</div>
            <div class="card-body">
                @if ($userRooms->count() > 0)
                    <ul class="list-group">
                        @foreach ($userRooms as $room)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('rooms.show', $room) }}">{{ $room->name }}</a>
                                    <small class="text-muted d-block">{{ $room->description }}</small>
                                    @if ($room->is_private)
                                        <span class="badge bg-info">Privada</span>
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