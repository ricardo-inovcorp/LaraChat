@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Perfil de Utilizador</div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            @if($user->avatar)
                                <img src="{{ $user->avatar }}?v={{ time() }}" alt="{{ $user->name }}" class="img-thumbnail rounded-circle mb-3" style="width: 200px; height: 200px; object-fit: cover;">
                            @else
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 200px; height: 200px; font-size: 80px;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-8">
                            <h2>{{ $user->name }}</h2>
                            <p class="text-muted">{{ $user->email }}</p>
                            
                            <div class="mb-3">
                                <strong>Permissão:</strong> 
                                <span class="badge {{ $user->permissions === 'admin' ? 'bg-danger' : 'bg-info' }}">
                                    {{ ucfirst($user->permissions) }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Estado:</strong> 
                                <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                    {{ $user->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Membro desde:</strong> {{ $user->created_at->format('d/m/Y') }}
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">Voltar</a>
                                
                                @if(Auth::user()->isAdmin())
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal">
                                        Editar
                                    </button>
                                @endif
                                
                                <a href="{{ route('messages.conversation', $user->id) }}" class="btn btn-primary">Enviar Mensagem</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->isAdmin())
    <!-- Modal para editar permissões e status -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.update-status', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar {{ $user->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permissions" class="form-label">Permissão</label>
                            <select name="permissions" id="permissions" class="form-select">
                                <option value="user" {{ $user->permissions === 'user' ? 'selected' : '' }}>User</option>
                                <option value="admin" {{ $user->permissions === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select name="status" id="status" class="form-select">
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
@endsection 