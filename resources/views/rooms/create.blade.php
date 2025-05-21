@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                @if(Auth::user()->isAdmin())
                    Criar Nova Sala
                @else
                    Criar Nova Sala Privada
                @endif
            </div>
            
            @if(!Auth::user()->isAdmin())
            <div class="alert alert-info m-3">
                Apenas administradores podem criar salas públicas. Como usuário comum, você só pode criar salas privadas.
            </div>
            @endif
            
            <div class="card-body">
                <form action="{{ route('rooms.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Sala</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    @if(Auth::user()->isAdmin())
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" {{ old('is_private') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_private">Sala Privada</label>
                    </div>
                    @else
                        <input type="hidden" name="is_private" value="1">
                    @endif
                    
                    <div class="mb-3">
                        <label for="members" class="form-label">Convidar Membros</label>
                        <select class="form-select" id="members" name="members[]" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Ctrl+clique para selecionar múltiplos usuários.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Criar Sala</button>
                        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 