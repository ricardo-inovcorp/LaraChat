@extends('layouts.app')

@php
use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                Editar Sala: {{ $room->name }}
            </div>
            
            <div class="card-body">
                <form action="{{ route('rooms.update', $room) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Sala <small class="text-muted">(máx. 20 caracteres)</small></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $room->name) }}" required maxlength="20">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição <small class="text-muted">(máx. 30 caracteres)</small></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="30">{{ old('description', $room->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" {{ old('is_private', $room->is_private) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_private">Sala Privada</label>
                    </div>
                    
                    <div class="mb-3">
                        <label for="members" class="form-label">Membros</label>
                        <select class="form-select" id="members" name="members[]" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, $members) ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Ctrl+clique para selecionar múltiplos usuários.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Atualizar Sala</button>
                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 