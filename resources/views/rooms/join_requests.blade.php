@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1>Solicitações de Acesso - {{ $room->name }}</h1>
        <p>Gerencie solicitações de usuários que querem participar desta sala.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('rooms.show', $room) }}" class="btn btn-secondary">Voltar para Sala</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Solicitações Pendentes</div>
            <div class="card-body">
                @if ($pendingRequests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Data da Solicitação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingRequests as $request)
                                    <tr>
                                        <td>{{ $request->user->name }}</td>
                                        <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <form action="{{ route('join-requests.approve', $request) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Aprovar</button>
                                                </form>
                                                <form action="{{ route('join-requests.reject', $request) }}" method="POST" class="d-inline ms-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger">Rejeitar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">Não há solicitações pendentes para esta sala.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 