@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Utilizadores</div>

                <div class="card-body">
                    <form action="{{ route('users.index') }}" method="GET" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="query" class="form-control" placeholder="Buscar por nome ou email..." value="{{ $query ?? '' }}">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                        </div>
                    </form>

                    @if(count($users) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Permissão</th>
                                        <th>Estado</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                @if($user->avatar)
                                                    <img src="{{ $user->avatar }}?v={{ time() }}" alt="{{ $user->name }}" class="rounded-circle" width="40" height="40">
                                                @else
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge {{ $user->permissions === 'admin' ? 'bg-danger' : 'bg-info' }}">
                                                    {{ ucfirst($user->permissions) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $user->status === 'active' ? 'Ativo' : 'Inativo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-primary">Ver</a>
                                                
                                                @if(Auth::user()->id != $user->id)
                                                    <a href="{{ route('messages.conversation', $user->id) }}" class="btn btn-sm btn-success">
                                                        <i class="bi bi-chat-dots"></i> Mensagem
                                                    </a>
                                                @endif
                                                
                                                @if(Auth::user()->isAdmin())
                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $user->id }}">
                                                        Editar
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal para editar permissões e status -->
                                        @if(Auth::user()->isAdmin())
                                            <div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $user->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('users.update-status', $user->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel{{ $user->id }}">Editar {{ $user->name }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="permissions{{ $user->id }}" class="form-label">Permissão</label>
                                                                    <select name="permissions" id="permissions{{ $user->id }}" class="form-select">
                                                                        <option value="user" {{ $user->permissions === 'user' ? 'selected' : '' }}>User</option>
                                                                        <option value="admin" {{ $user->permissions === 'admin' ? 'selected' : '' }}>Admin</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status{{ $user->id }}" class="form-label">Estado</label>
                                                                    <select name="status" id="status{{ $user->id }}" class="form-select">
                                                                        <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Ativo</option>
                                                                        <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inativo</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Salvar</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{ $users->links() }}
                    @else
                        <p class="text-center">Nenhum usuário encontrado.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 