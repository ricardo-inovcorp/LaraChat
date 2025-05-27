@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contas</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                        <i class="bi bi-plus-lg"></i> Nova Conta
                    </button>
                </div>

                <div class="card-body">
                    @if($accounts->isEmpty())
                        <p class="text-center text-muted">Nenhuma conta encontrada.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Membros</th>
                                        <th>Convites Pendentes</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts as $account)
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>
                                                @foreach($account->members as $member)
                                                    <span class="badge bg-info me-1">{{ $member->name }}</span>
                                                @endforeach
                                            </td>
                                            <td>{{ $account->invitations->where('status', 'pending')->count() }}</td>
                                            <td>
                                                <x-account-invitations :account="$account" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Criação de Conta -->
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccountModalLabel">Nova Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Conta</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 