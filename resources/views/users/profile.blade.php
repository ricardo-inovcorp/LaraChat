@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Meu Perfil</span>
                    <span class="badge {{ Auth::user()->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                        {{ Auth::user()->status === 'active' ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-content" type="button" role="tab" aria-controls="profile-content" aria-selected="true">
                                <i class="bi bi-person-circle me-2"></i>Informações Pessoais
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-content" type="button" role="tab" aria-controls="password-content" aria-selected="false">
                                <i class="bi bi-key me-2"></i>Alterar Senha
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-content" type="button" role="tab" aria-controls="account-content" aria-selected="false">
                                <i class="bi bi-shield-lock me-2"></i>Conta e Permissões
                            </button>
                        </li>
                    </ul>

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Informações Pessoais -->
                            <div class="tab-pane fade show active" id="profile-content" role="tabpanel" aria-labelledby="profile-tab">
                                <div class="row">
                                    <div class="col-md-4 text-center mb-4">
                                        <div class="avatar-upload">
                                            @if($user->avatar)
                                                <img src="{{ $user->avatar }}?v={{ time() }}" alt="{{ $user->name }}" class="img-thumbnail rounded-circle">
                                            @else
                                                <div class="avatar-placeholder">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <label for="avatar" class="avatar-upload-icon">
                                                <i class="bi bi-camera-fill"></i>
                                                <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*">
                                            </label>
                                        </div>
                                        
                                        <p class="text-muted small mt-2">Clique no ícone da câmera para alterar sua foto</p>
                                        
                                        @error('avatar')
                                            <div class="alert alert-danger mt-2">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nome</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" autofocus>
                                            </div>
                                            @error('name')
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                                            </div>
                                            @error('email')
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alterar Senha -->
                            <div class="tab-pane fade" id="password-content" role="tabpanel" aria-labelledby="password-tab">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>Deixe os campos em branco se não desejar alterar sua senha
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Nova Senha</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password">
                                            </div>
                                            @error('password')
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                        
                                        <div class="mb-3">
                                            <label for="password-confirm" class="form-label">Confirmar Nova Senha</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Conta e Permissões -->
                            <div class="tab-pane fade" id="account-content" role="tabpanel" aria-labelledby="account-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="bi bi-shield me-2"></i>Nível de Permissão</h5>
                                                <p class="card-text">
                                                    <span class="badge {{ $user->permissions === 'admin' ? 'bg-danger' : 'bg-info' }} p-2 fs-6">
                                                        <i class="bi {{ $user->permissions === 'admin' ? 'bi-shield-lock-fill' : 'bi-person' }} me-1"></i>
                                                        {{ ucfirst($user->permissions) }}
                                                    </span>
                                                </p>
                                                <p class="text-muted small">
                                                    @if($user->permissions === 'admin')
                                                        Você tem acesso administrativo ao sistema.
                                                    @else
                                                        Você tem acesso regular ao sistema.
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><i class="bi bi-person-badge me-2"></i>Membro Desde</h5>
                                                <p class="card-text">
                                                    <span class="fs-5">{{ $user->created_at->format('d/m/Y') }}</span>
                                                </p>
                                                <p class="text-muted small">
                                                    <i class="bi bi-clock-history me-1"></i>
                                                    {{ $user->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 border-top pt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para manter a aba ativa mesmo após reload -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Verifica se há um hash na URL
        if (window.location.hash) {
            // Ativa a aba correspondente ao hash
            const tabId = window.location.hash.substring(1) + '-tab';
            const tab = document.getElementById(tabId);
            if (tab) {
                const bsTab = new bootstrap.Tab(tab);
                bsTab.show();
            }
        }
        
        // Atualiza o hash quando a aba muda
        const tabs = document.querySelectorAll('#profileTabs button');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const contentId = e.target.getAttribute('data-bs-target').substring(1);
                const tabName = contentId.replace('-content', '');
                window.location.hash = tabName;
            });
        });
    });
</script>
@endsection 